<?php

namespace App\Services\BookReturns;

use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use App\Models\Purchases\Purchase;
use App\Models\Purchases\PurchaseItem;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoiceItem;
use App\Models\Stock\InventoryItem;
use App\Models\Stock\StockMovement;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookReturnService
{
    private const ACTIVE_RETURN_STATUSES = [
        'pending',
        'approved',
        'completed',
    ];

    /**
     * Registrar una devolución a proveedor.
     *
     * Cada elemento debe contener:
     * purchase_receipt_item_id, quantity, condition y notes opcional.
     */
    
    public function createSupplierReturn(array $data): array
    {
    return DB::transaction(function () use ($data) {
        $key = $data['idempotency_key'] ?? (string) Str::uuid();

        $existing = DB::table('book_return_purchases')
            ->where('idempotency_key', $key)
            ->value('id');

        if ($existing) {
            return [
                'type' => 'supplier',
                'id' => $existing,
                'duplicate' => true,
            ];
        }

        $purchase = Purchase::query()
            ->lockForUpdate()
            ->findOrFail($data['purchase_id']);

        $requested = collect($data['items'])
            ->groupBy(fn ($item) => (int) $item['purchase_item_id']);

        $calculated = [];

        foreach ($requested as $sourceId => $entries) {
            $source = PurchaseItem::query()
                ->where('purchase_id', $purchase->id)
                ->lockForUpdate()
                ->find($sourceId);

            if (! $source) {
                $this->fail(
                    'items',
                    'Una línea seleccionada no pertenece a la compra indicada.'
                );
            }

            $quantity = round(
                (float) $entries->sum(
                    fn ($entry) => (float) $entry['quantity']
                ),
                4
            );

            $remaining = $this->remainingSupplierQuantity($source);

            if ($quantity <= 0 || $quantity > $remaining + 0.00001) {
                $this->fail(
                    'items',
                    "La cantidad a devolver del producto {$source->product_name} supera el máximo permitido ({$remaining})."
                );
            }

            $product = Product::find($source->product_id);

            if (! $product) {
                $this->fail(
                    'items',
                    "No se encontró el producto de la línea {$source->id}."
                );
            }

            // La variante es opcional. Si la compra tiene una asociada,
            // la conservamos para identificar el inventario original.
            $variant = $source->product_variant_id
                ? ProductVariant::find($source->product_variant_id)
                : null;

            foreach ($entries as $entry) {
                $calculated[] = [
                    'source' => $source,
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => round((float) $entry['quantity'], 4),
                    'condition' => $entry['condition'] ?? 'sellable',
                    'notes' => $entry['notes'] ?? null,
                ];
            }
        }

        if ($calculated === []) {
            $this->fail('items', 'Agregá al menos un libro a la devolución.');
        }

        $returnId = DB::table('book_return_purchases')->insertGetId([
            'purchase_id' => $purchase->id,
            'provider_id' => $purchase->provider_id,
            'return_date' => $data['return_date'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
            'idempotency_key' => $key,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($calculated as $line) {
            DB::table('book_return_purchase_items')->insert([
                'book_return_purchase_id' => $returnId,
                'purchase_item_id' => $line['source']->id,
                'product_id' => $line['product']->id,
                'product_variant_id' => $line['variant']?->id,
                'quantity' => $line['quantity'],
                'condition' => $line['condition'],
                'notes' => $line['notes'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [
            'type' => 'supplier',
            'id' => $returnId,
            'duplicate' => false,
        ];
    }, 3);
}


    /**
     * Registrar una devolución de cliente.
     *
     * Cada elemento debe contener:
     * invoice_item_id, quantity, condition y notes opcional.
     */
    public function createCustomerReturn(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $key = $data['idempotency_key'] ?? (string) Str::uuid();

            $existing = DB::table('book_return_sales')
                ->where('idempotency_key', $key)
                ->value('id');

            if ($existing) {
                return [
                    'type' => 'customer',
                    'id' => $existing,
                    'duplicate' => true,
                ];
            }

            $invoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($data['invoice_id']);

            if ($invoice->receipt_types_prefix !== 'FV'
                && $invoice->receipt_types_prefix !== null) {
                $this->fail(
                    'invoice_id',
                    'El comprobante seleccionado no es una factura de venta.'
                );
            }

            if (! $invoice->stock_applied_at) {
                $this->fail(
                    'invoice_id',
                    'La factura todavía no tiene el stock de la venta aplicado.'
                );
            }

            $requested = collect($data['items'])
                ->groupBy(fn ($item) => (int) $item['invoice_item_id']);

            $calculated = [];

            foreach ($requested as $sourceId => $entries) {
                $source = InvoiceItem::query()
                    ->where('invoice_id', $invoice->id)
                    ->lockForUpdate()
                    ->find($sourceId);

                if (! $source) {
                    $this->fail(
                        'items',
                        'Una línea seleccionada no pertenece a la factura indicada.'
                    );
                }

                if (! $source->product_id || ! $source->product_variant_id) {
                    $this->fail(
                        'items',
                        "La línea {$source->id} no tiene un producto y una variante asociados."
                    );
                }

                $quantity = round(
                    (float) $entries->sum(fn ($entry) => (float) $entry['quantity']),
                    4
                );

                $remaining = $this->remainingCustomerQuantity($source);

                if ($quantity <= 0 || $quantity > $remaining + 0.00001) {
                    $this->fail(
                        'items',
                        "La cantidad a devolver de {$source->product_name} supera el máximo permitido ({$remaining})."
                    );
                }

                foreach ($entries as $entry) {
                    $calculated[] = [
                        'source' => $source,
                        'quantity' => round((float) $entry['quantity'], 4),
                        'condition' => $entry['condition'] ?? 'sellable',
                        'notes' => $entry['notes'] ?? null,
                    ];
                }
            }

            if ($calculated === []) {
                $this->fail('items', 'Agregá al menos un libro a la devolución.');
            }

            $returnId = DB::table('book_return_sales')->insertGetId([
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'status' => 'pending',
                'idempotency_key' => $key,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($calculated as $line) {
                DB::table('book_return_sale_items')->insert([
                    'book_return_sale_id' => $returnId,
                    'invoice_item_id' => $line['source']->id,
                    'product_id' => $line['source']->product_id,
                    'product_variant_id' => $line['source']->product_variant_id,
                    'quantity' => $line['quantity'],
                    'condition' => $line['condition'],
                    'notes' => $line['notes'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return [
                'type' => 'customer',
                'id' => $returnId,
                'duplicate' => false,
            ];
        }, 3);
    }

    /**
     * Cambia el estado de una devolución.
     *
     * pending -> approved | rejected
     * approved -> completed | rejected
     *
     * El stock se modifica únicamente al completar.
     */
    public function updateStatus(string $type, int $id, string $status): array
    {
        if (! in_array($type, ['supplier', 'customer'], true)) {
            $this->fail('type', 'Tipo de devolución inválido.');
        }

        if (! in_array($status, ['approved', 'rejected', 'completed'], true)) {
            $this->fail('status', 'El estado solicitado no es válido.');
        }

        return DB::transaction(function () use ($type, $id, $status) {
            $table = $type === 'supplier'
                ? 'book_return_purchases'
                : 'book_return_sales';

            $itemTable = $type === 'supplier'
                ? 'book_return_purchase_items'
                : 'book_return_sale_items';

            $foreignKey = $type === 'supplier'
                ? 'book_return_purchase_id'
                : 'book_return_sale_id';

            $return = DB::table($table)
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (! $return) {
                $this->fail('return', 'No se encontró la devolución.');
            }

            if ($return->status === $status) {
                return [
                    'type' => $type,
                    'id' => $id,
                    'status' => $status,
                    'already_processed' => true,
                ];
            }

            if ($return->status === 'rejected' || $return->status === 'completed') {
                $this->fail(
                    'status',
                    'Una devolución rechazada o completada no puede cambiar de estado.'
                );
            }

            if ($status === 'completed' && $return->status !== 'approved') {
                $this->fail(
                    'status',
                    'Primero se debe aprobar la devolución.'
                );
            }

            if ($status === 'rejected' && $return->stock_applied_at) {
                $this->fail(
                    'status',
                    'No se puede rechazar una devolución cuyo stock ya fue aplicado.'
                );
            }

            if ($status === 'completed') {
                if ($return->stock_applied_at) {
                    $this->fail(
                        'status',
                        'El movimiento de stock ya fue aplicado.'
                    );
                }

                $lines = DB::table($itemTable)
                    ->where($foreignKey, $id)
                    ->orderBy('id')
                    ->get();

                if ($lines->isEmpty()) {
                    $this->fail('items', 'La devolución no tiene líneas.');
                }

                foreach ($lines as $line) {
                    if ($type === 'supplier') {
                        $this->applySupplierStock($return, $line);
                    } elseif ($line->condition === 'sellable') {
                        $this->applyCustomerStock($return, $line);
                    }
                }

                DB::table($table)->where('id', $id)->update([
                    'status' => 'completed',
                    'stock_applied_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table($table)->where('id', $id)->update([
                    'status' => $status,
                    'updated_at' => now(),
                ]);
            }

            return [
                'type' => $type,
                'id' => $id,
                'status' => $status,
                'already_processed' => false,
            ];
        }, 3);
    }

    
    private function applySupplierStock(object $return, object $line): void
    {
    $source = PurchaseItem::query()
        ->with('purchase')
        ->findOrFail($line->purchase_item_id);

    $purchase = $source->purchase;
    $product = Product::findOrFail($line->product_id);

    $variant = $line->product_variant_id
        ? ProductVariant::query()
            ->with('product')
            ->lockForUpdate()
            ->find($line->product_variant_id)
        : null;

    $inventoryQuery = InventoryItem::query()
        ->where('product_id', $product->id)
        ->where('branch_name', $purchase->branch_name)
        ->where('warehouse_name', $purchase->warehouse_name);

    if ($line->product_variant_id) {
        $inventoryQuery->where(
            'product_variant_id',
            $line->product_variant_id
        );
    } else {
        $inventoryQuery->whereNull('product_variant_id');
    }

    $inventory = $inventoryQuery
        ->orderBy('id')
        ->lockForUpdate()
        ->first();

    if (! $inventory) {
        $this->fail(
            'items',
            "No se encontró el inventario original de {$product->name}."
        );
    }

    $before = (float) $inventory->current_stock;
    $quantity = (float) $line->quantity;

    if ($quantity > $before + 0.00001) {
        $this->fail(
            'items',
            "No hay stock suficiente para devolver {$product->name} al proveedor."
        );
    }

    $after = round($before - $quantity, 4);

    $inventory->update(['current_stock' => $after]);

    StockMovement::create([
        'inventory_item_id' => $inventory->id,
        'movement_type' => 'supplier_return',
        'quantity' => -$quantity,
        'stock_before' => $before,
        'stock_after' => $after,
        'reference_type' => 'book_return_purchase',
        'reference_id' => $return->id,
        'notes' => 'Devolución a proveedor',
    ]);

    $stockService = app(StockService::class);

    if ($variant) {
        $stockService->recalculateVariantStock($variant);
    }

    $stockService->recalculateProductStock($product);
}


    private function applyCustomerStock(object $return, object $line): void
    {
        $variant = ProductVariant::query()
            ->with('product')
            ->lockForUpdate()
            ->find($line->product_variant_id);

        if (! $variant) {
            $this->fail('items', 'No se encontró la variante del libro devuelto.');
        }

        $source = InvoiceItem::query()->findOrFail($line->invoice_item_id);
        $invoice = Invoice::query()->findOrFail($return->invoice_id);

        $warehouseId = $source->warehouse_id ?: $invoice->warehouse_id;
        $warehouse = $warehouseId
            ? \App\Models\Stock\Warehouse::with('branch')->find($warehouseId)
            : null;

        $inventoryQuery = InventoryItem::query()
            ->where('product_variant_id', $variant->id);

        if ($warehouse) {
            $inventoryQuery
                ->where('warehouse_name', $warehouse->name)
                ->where('branch_name', $warehouse->branch?->name);
        }

        $inventory = $inventoryQuery
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        if (! $inventory) {
            $this->fail(
                'items',
                "No se encontró el depósito original de {$source->product_name}. No se modificó el stock."
            );
        }

        $before = (float) $inventory->current_stock;
        $quantity = (float) $line->quantity;
        $after = round($before + $quantity, 4);

        $inventory->update(['current_stock' => $after]);

        StockMovement::create([
            'inventory_item_id' => $inventory->id,
            'movement_type' => 'customer_return',
            'quantity' => $quantity,
            'stock_before' => $before,
            'stock_after' => $after,
            'reference_type' => 'book_return_sale',
            'reference_id' => $return->id,
            'notes' => 'Devolución física de factura '.$invoice->display_number,
        ]);

        app(StockService::class)->recalculateVariantStock($variant);
        app(StockService::class)->recalculateProductStock($variant->product);
    }

    
    private function remainingSupplierQuantity(PurchaseItem $item): float
    {
    $returned = (float) DB::table('book_return_purchase_items as i')
        ->join(
            'book_return_purchases as r',
            'r.id',
            '=',
            'i.book_return_purchase_id'
        )
        ->where('i.purchase_item_id', $item->id)
        ->whereIn('r.status', self::ACTIVE_RETURN_STATUSES)
        ->sum('i.quantity');

    return max(
        0,
        round(abs((float) $item->quantity) - $returned, 4)
    );
}


    private function remainingCustomerQuantity(InvoiceItem $item): float
    {
        $returned = (float) DB::table('book_return_sale_items as i')
            ->join(
                'book_return_sales as r',
                'r.id',
                '=',
                'i.book_return_sale_id'
            )
            ->where('i.invoice_item_id', $item->id)
            ->whereIn('r.status', self::ACTIVE_RETURN_STATUSES)
            ->sum('i.quantity');

        return max(
            0,
            round(abs((float) $item->quantity) - $returned, 4)
        );
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
