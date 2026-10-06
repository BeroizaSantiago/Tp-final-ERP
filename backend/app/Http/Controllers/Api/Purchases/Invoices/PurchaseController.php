<?php

namespace App\Http\Controllers\Api\Purchases\Invoices;

use App\Http\Controllers\Controller;
use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use App\Models\Purchases\Provider;
use App\Models\Purchases\Purchase;
use App\Models\Purchases\PurchaseItem;
use App\Models\Stock\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\Finance\ProviderPaymentOrderService;

/**
 * Controlador de Compra.
 *
 * Coordina las solicitudes, validaciones y respuestas del módulo Compra del ERP.
 */
class PurchaseController extends Controller
{
    public function __construct(private readonly ProviderPaymentOrderService $paymentOrderService) {}
    public function index(Request $request)
    {
        $search = $request->query('search');

        return Purchase::query()
            ->with('provider')
            ->when($search, function ($query) use ($search) {
                $query->where('full_number', 'like', "%{$search}%")
                    ->orWhere('provider_name', 'like', "%{$search}%")
                    ->orWhere('receipt_type_name', 'like', "%{$search}%")
                    ->orWhere('status_name', 'like', "%{$search}%");
            })
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(20);
    }

    public function show(Purchase $purchase)
    {
        return $purchase->load([
            'provider',
            'items.product',
            'items.variant.size',
            'items.variant.color',
            'payments',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'provider_id' => ['required', 'exists:providers,id'],
            'issue_date' => ['required', 'date'],
            'payment_due_date' => ['nullable', 'date'],
            'vat_imputation_date' => ['nullable', 'date'],

            'receipt_type_name' => ['required', 'string'],
            'letter' => ['nullable', 'string'],
            'first_number' => ['nullable', 'string'],
            'second_number' => ['nullable', 'string'],
            'branch_name' => ['required', 'string', 'max:255'],
            'warehouse_name' => ['required', 'string', 'max:255'],
            'currency_name' => ['nullable', 'string', 'max:100'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percentage' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_percentage' => ['nullable', 'numeric', 'min:0'],
            'perceptions'=>['nullable','array'], 'perceptions.*.tax_type'=>['required','string','max:100'],
            'perceptions.*.regime_name'=>['required','string','max:255'], 'perceptions.*.amount'=>['required','numeric','min:0.01'],
            'perceptions.*.calculated_amount'=>['nullable','numeric','min:0'], 'perceptions.*.is_automatic'=>['nullable','boolean'],
        ]);

        return DB::transaction(function () use ($data) {
            $provider = Provider::findOrFail($data['provider_id']);

            $taxedAmount = 0;
            $taxAmount = 0;
            $totalAmount = 0;

            $calculatedItems = [];

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                // La variante es opcional: si el producto maneja una sola, se
                // usa la primera; si no tiene ninguna, la compra igual se
                // permite y el ítem queda sin variante.
                $variant = null;

                if (! empty($item['product_variant_id'])) {
                    $variant = ProductVariant::with(['size', 'color'])
                        ->findOrFail($item['product_variant_id']);

                    if ((int) $variant->product_id !== (int) $product->id) {
                        throw new \InvalidArgumentException(
                            'La variante seleccionada no pertenece al producto.'
                        );
                    }
                } else {
                    $variant = ! $product->has_variants
                        ? app(\App\Services\StockService::class)->ensureTechnicalVariant($product)
                        : ProductVariant::with(['size', 'color'])
                            ->where('product_id', $product->id)
                            ->where('is_active', true)
                            ->first();
                }

                $qty = (float) $item['quantity'];
                $price = (float) $item['unit_price'];
                $discount = (float) ($item['discount_percentage'] ?? 0);
                $iva = (float) ($item['tax_percentage'] ?? 21);

                $lineTotal = $qty * $price;
                $lineTotal -= $lineTotal * ($discount / 100);

                $net = $iva > 0
                    ? $lineTotal / (1 + ($iva / 100))
                    : $lineTotal;

                $lineTax = $lineTotal - $net;

                $taxedAmount += $net;
                $taxAmount += $lineTax;
                $totalAmount += $lineTotal;

                $calculatedItems[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'qty' => $qty,
                    'price' => $price,
                    'discount' => $discount,
                    'iva' => $iva,
                    'net' => $net,
                    'line_tax' => $lineTax,
                    'line_total' => $lineTotal,
                ];
            }

            $prefix = $this->prefixFromReceiptType($data['receipt_type_name']);

            $fullNumber = trim(
                ($data['letter'] ?? '') . '-' .
                ($data['first_number'] ?? '') . '-' .
                ($data['second_number'] ?? '')
            , '-');

            $purchase = Purchase::create([
                'provider_id' => $provider->id,
                'provider_name' => $provider->name . ' - ' . $provider->identification_number,
                'branch_name' => $data['branch_name'],
                'warehouse_name' => $data['warehouse_name'],

                'issue_date' => $data['issue_date'],
                'payment_due_date' => $data['payment_due_date'] ?? $data['issue_date'],
                'vat_imputation_date' => $data['vat_imputation_date'] ?? $data['issue_date'],

                'receipt_type_name' => $data['receipt_type_name'],
                'receipt_types_prefix' => $prefix,
                'letter' => $data['letter'] ?? null,
                'first_number' => $data['first_number'] ?? null,
                'second_number' => $data['second_number'] ?? null,
                'full_number' => $fullNumber,

                'currency_name' => 'Pesos',

                'taxed_amount' => $taxedAmount,
                'tax_amount' => $taxAmount,
                'non_taxed_amount' => 0,
                'exempt_amount' => 0,
                'discount_amount' => 0,
                'surcharge_amount' => 0,
                'total_amount' => $totalAmount,
                'balance' => $totalAmount,

                'status_name' => 'Pendiente',
                'status_id' => 1,
            ]);

            foreach ($calculatedItems as $calculated) {
                $product = $calculated['product'];
                $variant = $calculated['variant'];

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,

                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,

                    'product_code' => $product->code,
                    'product_name' => $product->name,

                    'size_id' => $variant?->size_id,
                    'size_name' => $variant?->size?->name,

                    'color_id' => $variant?->color_id,
                    'color_name' => $variant?->color?->name,

                    'quantity' => $calculated['qty'],
                    'unit_price' => $calculated['price'],
                    'discount_percentage' => $calculated['discount'],
                    'tax_percentage' => $calculated['iva'],

                    'subtotal_amount' => $calculated['net'],
                    'tax_amount' => $calculated['line_tax'],
                    'total_amount' => $calculated['line_total'],
                ]);

                if ($variant) {
                    $newStock = (float) $variant->current_stock + (float) $calculated['qty'];

                    $variant->update([
                        'current_stock' => $newStock,
                        'available_stock' => $newStock,
                    ]);
                }

                $inventoryQuery = InventoryItem::query()
                    ->where('product_id', $product->id)
                    ->where('branch_name', $data['branch_name'])
                    ->where('warehouse_name', $data['warehouse_name']);

                if ($variant) {
                    $inventoryQuery->where('product_variant_id', $variant->id);
                } else {
                    $inventoryQuery->whereNull('product_variant_id');
                }

                $inventoryItem = $inventoryQuery->firstOrNew([]);
                $inventoryStockBefore = (float) ($inventoryItem->current_stock ?? 0);
                $inventoryStockAfter = $inventoryStockBefore + (float) $calculated['qty'];
                $previousUnitCost = (float) (
                    $inventoryItem->valued_item
                    ?: $product->cost_with_discount
                    ?: $product->replacement_cost
                    ?: $product->last_purchase_price
                    ?: 0
                );
                $purchasedUnitCost = (float) $calculated['price']
                    * (1 - ((float) $calculated['discount'] / 100));
                $weightedUnitCost = $inventoryStockAfter > 0
                    ? (($inventoryStockBefore * $previousUnitCost)
                        + ((float) $calculated['qty'] * $purchasedUnitCost))
                        / $inventoryStockAfter
                    : $purchasedUnitCost;

                $inventoryItem->fill([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'warehouse_name' => $data['warehouse_name'],
                    'product_external_id' => $product->external_id,
                    'product_variant_external_id' => $variant?->external_id,
                    'code' => $product->code,
                    'bar_code' => $variant?->bar_code ?: $product->bar_code,
                    'reference_code' => $product->reference_code,
                    'product_name' => $product->name,
                    'branch_name' => $data['branch_name'],
                    'current_stock' => $inventoryStockAfter,
                    'color_name' => $variant?->color?->name,
                    'size_name' => $variant?->size?->name,
                    'valued_item' => round($weightedUnitCost, 4),
                    'currency_symbol' => $product->currency_symbol ?: '$',
                ])->save();

                $stockService = app(\App\Services\StockService::class);
                if ($variant) {
                    $stockService->recalculateVariantStock($variant);
                }
                $stockService->recalculateProductStock($product);
            }

            return $purchase->fresh()->load([
                'provider',
                'items.product',
                'items.variant.size',
                'items.variant.color',
            ]);
        });
    }

    private function prefixFromReceiptType(string $type): string
    {
        return match ($type) {
            'Factura' => 'FC',
            'Nota de Crédito' => 'NC',
            'Nota de Débito' => 'ND',
            'Ticket' => 'TK',
            'Recibo' => 'RC',
            'Sin Factura' => 'SF',
            default => 'CP',
        };
    }

    public function storePayment(Request $request, Purchase $purchase)
{
    $data = $request->validate([
        'payment_method' => ['required', Rule::in(['card', 'cash', 'transfer', 'check'])],
        'amount' => ['required', 'numeric', 'min:0.01'],
        'discount_amount' => ['nullable', 'numeric', 'min:0'],
        'surcharge_amount' => ['nullable', 'numeric', 'min:0'],
        'bank_name' => ['nullable', 'string'],
        'reference' => ['nullable', 'string'],
        'notes' => ['nullable', 'string'],
    ]);

    $amount = (float) $data['amount'];
    $discount = (float) ($data['discount_amount'] ?? 0);
    $surcharge = (float) ($data['surcharge_amount'] ?? 0);

    $totalPaid = $amount - $discount + $surcharge;

    if ($data['payment_method'] === 'supplier_account') {
        $realPaid = (float) $purchase->payments()
            ->where('payment_method', '!=', 'supplier_account')
            ->sum('total_paid');

        $pendingBalance = max(0, (float) $purchase->total_amount - $realPaid);

        if ($amount > $pendingBalance) {
            return response()->json([
                'message' => 'El importe enviado a cuenta corriente supera el saldo pendiente.',
            ], 422);
        }

        $purchase->payments()->create([
            'payment_method' => 'supplier_account',
            'amount' => $amount,
            'discount_amount' => 0,
            'surcharge_amount' => 0,
            'total_paid' => 0,
            'bank_name' => null,
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $purchase->update([
            'balance' => $pendingBalance,
            'status_name' => 'Pendiente',
            'status_id' => 1,
        ]);

        return $purchase->load('payments', 'provider', 'items');
    }

    if (!$purchase->provider) {
        return response()->json(['message' => 'La compra debe tener un proveedor para emitir la Orden de Pago.'], 422);
    }

    $order = $this->paymentOrderService->create($purchase->provider, [
        'payment_method' => $data['payment_method'],
        'amount' => $totalPaid,
        'origin' => 'cash_purchase',
        'purchase_ids' => [$purchase->id],
        'bank_name' => $data['bank_name'] ?? null,
        'reference' => $data['reference'] ?? null,
        'notes' => $data['notes'] ?? null,
    ], $request->user()?->id);

    return $purchase->fresh()->load('payments', 'provider', 'items')->setAttribute('payment_order', $order);
}
}
