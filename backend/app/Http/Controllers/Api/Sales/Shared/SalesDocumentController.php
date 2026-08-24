<?php

namespace App\Http\Controllers\Api\Sales\Shared;

use App\Http\Controllers\Controller;
use App\Models\Clients\Client;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Products\Product;
use App\Models\Stock\InventoryItem;
use App\Models\Products\ProductVariant;
use App\Models\Sales\ExchangeTicket;
use App\Models\Sales\ExchangeTicketItem;
use Carbon\Carbon;

/**
 * Centraliza la gestion de documentos comerciales de clientes.
 *
 * Permite consultar y crear facturas, remitos, notas de credito, notas de
 * debito, notas de pedido y presupuestos. Tambien calcula importes e impuestos,
 * crea los renglones de cada documento y vincula comprobantes relacionados.
 */
abstract class SalesDocumentController extends Controller
{

    public function show(Invoice $invoice)
    {
        return $invoice->load([
            'client',
            'items.variant.size',
            'items.variant.color',
            'payments.voucher',
            'salesPromotion',
        ]);
    }

    protected function storeDocument(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'issue_date' => ['required', 'date'],
            'payment_due_date' => ['nullable', 'date'],
            'receipt_type_name' => ['required', 'string'],
            'letter' => ['required', 'string'],
            'first_number' => ['required', 'string'],
            'mode' => ['nullable', 'in:fiscal,internal'],
            'items' => ['required', 'array', 'min:1'],

            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.discount_percentage' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price_with_taxes' => ['nullable', 'numeric', 'min:0'],
            'is_bill_without_delivery' => ['nullable', 'boolean'],
            'sales_promotion_id' => ['nullable', 'exists:sales_promotions,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'perceptions'=>['nullable','array'], 'perceptions.*.tax_type'=>['required','string','max:100'],
            'perceptions.*.regime_name'=>['required','string','max:255'], 'perceptions.*.amount'=>['required','numeric','min:0.01'],
            'perceptions.*.calculated_amount'=>['nullable','numeric','min:0'], 'perceptions.*.is_automatic'=>['nullable','boolean'],

        ]);

        $affectsStock = $request->input('affects_stock', true);
        $stockOperation = $request->input('stock_operation', 'decrease');



        return DB::transaction(function () use ($data, $affectsStock, $stockOperation) {
            $client = Client::findOrFail($data['client_id']);

            $taxedAmount = 0;
            $taxAmount = 0;
            $totalAmount = 0;

            $calculatedItems = [];

            foreach ($data['items'] as $item) {
                $product = Product::with(['category', 'brand'])->findOrFail($item['product_id']);
                $isGenericProduct = $this->isGenericSaleProduct($product);
                $variant = ! empty($item['product_variant_id'])
                    ? ProductVariant::findOrFail($item['product_variant_id'])
                    : null;

                if (! $variant && ! $isGenericProduct) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => 'Seleccioná una variante válida para '.$product->name.'.',
                    ]);
                }

                if ($variant && (int) $variant->product_id !== (int) $product->id) {
                    throw new \InvalidArgumentException(
                        'La variante seleccionada no pertenece al producto.'
                    );
                }
                $qty = $item['quantity'];

                $priceWithTax = array_key_exists('unit_price_with_taxes', $item)
                    ? (float) $item['unit_price_with_taxes']
                    : (float) ($variant?->price_a_with_tax
                        ?? $product->price_a_with_tax
                        ?? 0);

                $iva = 21;
                $discount = $item['discount_percentage'] ?? 0;

                $lineTotal = $qty * $priceWithTax;
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
                    'price_with_tax' => $priceWithTax,
                    'iva' => $iva,
                    'discount' => $discount,
                    'line_total' => $lineTotal,
                    'net' => $net,
                    'line_tax' => $lineTax,
                ];
            }

            $invoice = Invoice::create([
                'client_id' => $client->id,
                // Se conserva el vendedor para reportes aun cuando la venta no genere movimiento de caja.
                'seller_full_name' => auth()->user()?->name,
                'user_name' => auth()->user()?->username ?: auth()->user()?->email,
                'customer_external_id' => $client->external_id,
                'customer_name' => $client->name . ' - ' . $client->document_number,
                'customer_email' => $client->email,
                'customer_phone_number' => $client->first_phone,

                'issue_date' => $this->issueDateWithTime($data['issue_date']),
                'payment_due_date' => $data['payment_due_date'] ?? $data['issue_date'],

                'receipt_type_name' => $data['receipt_type_name'],
                'receipt_types_prefix' => 'FV',
                'letter' => $data['letter'],
                'first_number' => $data['first_number'],
                'mode' => $data['mode'] ?? 'fiscal',

                'currency_name' => 'Pesos',
                'payment_condition_name' => 'CONTADO',

                'taxed_amount' => $taxedAmount,
                'tax_amount' => $taxAmount,
                'non_taxed_amount' => 0,
                'exempt_amount' => 0,
                'total_amount' => $totalAmount,
                'balance' => $totalAmount,

                'status_name' => 'Pend. Cobro',
                'status_id' => 6,
                'printed' => false,
                'is_credit' => false,
                'is_bill_without_delivery' => $data['is_bill_without_delivery'] ?? false,
                'has_pending_delivery' => $data['is_bill_without_delivery'] ?? false,
                'delivery_status' => ($data['is_bill_without_delivery'] ?? false) ? 'pending' : 'delivered',

            ]);

            if (($data['mode'] ?? null) === 'internal') {
                $invoice->update([
                    'full_number' => '0099-' . str_pad((string) $invoice->id, 8, '0', STR_PAD_LEFT),
                    'arca_status' => 'NOT_APPLICABLE',
                    'arca_result' => 'Comprobante interno',
                ]);
            }

            foreach ($calculatedItems as $calculated) {
                $product = $calculated['product'];
                $variant = $calculated['variant'];
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,

                    'product_external_id' => $product->external_id,
                    'product_search_code' => $product->bar_code ?: $product->reference_code,

                    'description' => $product->name,

                    'quantity' => $calculated['qty'],
                    'tax_aliquot_percentage' => $calculated['iva'],
                    'unit_price_with_taxes' => $calculated['price_with_tax'],
                    'unit_price' => $calculated['net'] / $calculated['qty'],
                    'discount_percentage' => $calculated['discount'],
                    'manual_discount_percentage' => $calculated['discount'],
                    'subtotal_amount' => $calculated['net'],
                    'tax_amount' => $calculated['line_tax'],
                    'total_amount' => $calculated['line_total'],

                    'product_name' => $product->name,
                    'product_code' => $product->code,
                    'product_barcode' => $product->bar_code,
                    'product_reference_code' => $product->reference_code,
                    'product_category_id' => $product->category_id,
                    'product_category_name' => $product->getRelation('category')?->name
                        ?: $product->getAttribute('category'),
                    'brand_name' => $product->getRelation('brand')?->name
                        ?: $product->getAttribute('brand'),
                    'model_name' => $product->model,
                    'product_variant_id' => $variant?->id,

                    'size_id' => $variant?->size_id,
                    'size_name' => $variant?->size?->name,

                    'color_id' => $variant?->color_id,
                    'color_name' => $variant?->color?->name,

                    'variant_barcode' => $variant?->bar_code,
                    'variant_price_a' => $variant?->price_a_with_tax,

                    'is_active' => true,


                ]);
                if ($affectsStock && $variant) {
                    if ($stockOperation === 'increase') {
                        $newStock = $variant->current_stock + $calculated['qty'];
                    } else {
                        $newStock = max(0, $variant->current_stock - $calculated['qty']);
                    }

                    $variant->update([
                        'current_stock' => $newStock,
                        'available_stock' => $newStock,
                    ]);

                    app(\App\Services\StockService::class)
                        ->recalculateProductStock($product);
                }
            }
            if (!empty($data['sales_promotion_id'])) {
                app(\App\Services\Sales\SalesPromotionService::class)->apply(
                    $invoice,
                    \App\Models\Products\SalesPromotion::findOrFail($data['sales_promotion_id'])
                );
            }
            return $invoice->fresh()->load('items', 'client', 'salesPromotion');
        });
    }

    /**
     * Los formularios comerciales permiten elegir el día, pero issue_date es
     * datetime. Si llega solamente una fecha, conserva ese día y registra la
     * hora real de creación en vez de dejar 00:00:00.
     */
    protected function isGenericSaleProduct(Product $product): bool
    {
        $genericCodes = ['ZZ', '00', '0000'];

        return collect([$product->code, $product->reference_code, $product->bar_code])
            ->contains(fn ($value) => in_array(strtoupper(trim((string) $value)), $genericCodes, true));
    }

    protected function issueDateWithTime(string $value): Carbon
    {
        $date = Carbon::parse($value, config('app.timezone'));

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value))) {
            $currentTime = now();
            $date->setTime($currentTime->hour, $currentTime->minute, $currentTime->second);
        }

        return $date;
    }


    public function storeExchangeTickets(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'valid_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $invoice->load([
            'items.variant',
        ]);

        return DB::transaction(function () use ($data, $invoice) {
            $tickets = [];

            for ($i = 0; $i < $data['quantity']; $i++) {
                $ticket = ExchangeTicket::create([
                    'invoice_id' => $invoice->id,
                    'ticket_number' => $this->generateExchangeTicketNumber(),
                    'expiration_date' => now()
                        ->addDays($data['valid_days'] ?? 30)
                        ->toDateString(),
                    'used' => false,
                ]);

                foreach ($invoice->items as $item) {
                    /*
                 * Primero intenta obtener el producto local mediante
                 * la variante elegida en la venta.
                 */
                    $productId = $item->variant?->product_id;

                    /*
                 * Si la venta no tiene variante o la relación no está
                 * disponible, busca el producto local mediante external_id.
                 */
                    if (!$productId && $item->product_external_id) {
                        $productId = Product::where(
                            'external_id',
                            $item->product_external_id
                        )->value('id');
                    }

                    ExchangeTicketItem::create([
                        'exchange_ticket_id' => $ticket->id,
                        'invoice_item_id' => $item->id,
                        'product_id' => $productId,
                        'quantity' => (int) $item->quantity,
                    ]);
                }

                $tickets[] = $ticket->load('items.invoiceItem');
            }

            return response()->json($tickets, 201);
        });
    }

    private function generateExchangeTicketNumber(): string
    {
        $nextId = (ExchangeTicket::max('id') ?? 0) + 1;

        return 'TCK-'
            . now()->format('Y')
            . '-'
            . str_pad((string) $nextId, 8, '0', STR_PAD_LEFT);
    }


}
