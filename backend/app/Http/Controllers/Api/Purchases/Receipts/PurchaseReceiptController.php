<?php

namespace App\Http\Controllers\Api\Purchases\Receipts;

use App\Http\Controllers\Controller;
use App\Models\Products\Product;
use App\Models\Purchases\Provider;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Purchases\PurchaseReceiptItem;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Stock\InventoryItem;
use App\Models\Stock\StockMovement;
use App\Services\StockService;

/**
 * Gestiona comprobantes de compra recibidos de proveedores.
 *
 * Calcula totales por items e impuestos, registra el comprobante y sus
 * renglones, incrementa el inventario y genera movimientos de stock para
 * dejar trazabilidad de la compra.
 */
class PurchaseReceiptController extends Controller
{
    public function index()
    {
        return PurchaseReceipt::with('provider')
            ->latest('issue_date')
            ->paginate(20);
    }

    public function show(PurchaseReceipt $purchaseReceipt)
    {
        return $purchaseReceipt->load('provider', 'items.product');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'provider_id' => ['required', 'exists:providers,id'],
            'issue_date' => ['required', 'date'],
            'receipt_type_name' => ['required', 'string'],
            'receipt_number' => ['required', 'string'],
            'currency_name' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_percentage' => ['nullable', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($data) {
            $provider = Provider::findOrFail($data['provider_id']);

            $total = 0;

            foreach ($data['items'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                $tax = $subtotal * (($item['tax_percentage'] ?? 0) / 100);
                $total += $subtotal + $tax;
            }

            $purchase = PurchaseReceipt::create([
                'provider_id' => $provider->id,
                'provider_name' => $provider->name,
                'issue_date' => $this->issueDateWithTime($data['issue_date']),
                'receipt_type_name' => $data['receipt_type_name'],
                'receipt_number' => $data['receipt_number'],
                'currency_name' => $data['currency_name'] ?? 'Pesos',
                'total_amount' => $total,
                'status_name' => 'Registrada',
                'status_id' => 1,
                'created_by' => 'system',
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $stockService = app(StockService::class);
                $variant = $stockService->ensureTechnicalVariant($product);

                $subtotal = $item['quantity'] * $item['unit_price'];
                $tax = $subtotal * (($item['tax_percentage'] ?? 0) / 100);
                $lineTotal = $subtotal + $tax;

                PurchaseReceiptItem::create([
                    'purchase_receipt_id' => $purchase->id,
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_percentage' => $item['tax_percentage'] ?? 0,
                    'tax_amount' => $tax,
                    'total_amount' => $lineTotal,
                ]);
                $inventoryItem = InventoryItem::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'branch_name' => 'SUCURSAL',
                    'warehouse_name' => 'DEPÓSITO RIOS LORENA BEATRIZ',
                ],
                [
                    'product_external_id' => $product->external_id,
                    'product_variant_external_id' => $variant->external_id,
                    'code' => $product->code,
                    'bar_code' => $product->bar_code,
                    'reference_code' => $product->reference_code,
                    'product_name' => $product->name,
                    'branch_name' => 'SUCURSAL',
                    'current_stock' => 0,
                    'currency_symbol' => '$',
                ]
                );

                $before = $inventoryItem->current_stock;
                $after = $before + $item['quantity'];

                $inventoryItem->update([
                    'current_stock' => $after,
                ]);

                StockMovement::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'movement_type' => 'purchase',
                    'quantity' => $item['quantity'],
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reference_type' => 'purchase_receipt',
                    'reference_id' => $purchase->id,
                    'notes' => 'Compra ' . $purchase->receipt_number,
                ]);

                $stockService->recalculateVariantStock($variant);
                $stockService->recalculateProductStock($product);
            }

            return $purchase->load('provider', 'items.product');
        });
    }

    private function issueDateWithTime(string $value): Carbon
    {
        $date = Carbon::parse($value, config('app.timezone'));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value))) {
            $time = now();
            $date->setTime($time->hour, $time->minute, $time->second);
        }

        return $date;
    }
}
