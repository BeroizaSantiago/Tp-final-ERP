<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reconstruye compras históricas y recalcula la secuencia documental de
     * compras/ventas. Solo escribe la bitácora: no modifica el stock actual.
     */
    public function up(): void
    {
        $operations = collect();

        DB::table('invoice_items as item')
            ->join('invoices as invoice', 'invoice.id', '=', 'item.invoice_id')
            ->whereNotNull('invoice.stock_applied_at')
            ->whereNotNull('item.product_variant_id')
            ->selectRaw('item.invoice_id as reference_id, item.product_id, item.product_variant_id, SUM(item.quantity) as quantity, MAX(invoice.full_number) as number, MAX(invoice.stock_applied_at) as movement_date')
            ->groupBy('item.invoice_id', 'item.product_id', 'item.product_variant_id')
            ->get()
            ->each(fn ($row) => $operations->push((object) [
                'type' => 'sale',
                'reference_type' => 'invoice',
                'reference_id' => $row->reference_id,
                'product_id' => $row->product_id,
                'variant_id' => $row->product_variant_id,
                'quantity' => -(float) $row->quantity,
                'number' => $row->number,
                'date' => $row->movement_date,
            ]));

        DB::table('purchase_items as item')
            ->join('purchases as purchase', 'purchase.id', '=', 'item.purchase_id')
            ->selectRaw('item.purchase_id as reference_id, item.product_id, item.product_variant_id, SUM(item.quantity) as quantity, MAX(purchase.full_number) as number, MAX(COALESCE(purchase.created_at, purchase.issue_date)) as movement_date')
            ->groupBy('item.purchase_id', 'item.product_id', 'item.product_variant_id')
            ->get()
            ->each(fn ($row) => $operations->push((object) [
                'type' => 'purchase',
                'reference_type' => 'purchase',
                'reference_id' => $row->reference_id,
                'product_id' => $row->product_id,
                'variant_id' => $row->product_variant_id,
                'quantity' => (float) $row->quantity,
                'number' => $row->number,
                'date' => $row->movement_date,
            ]));

        $resolved = $operations->map(function ($operation) {
            $inventoryQuery = DB::table('inventory_items')->where('product_id', $operation->product_id);
            if ($operation->variant_id) {
                $inventoryQuery->where('product_variant_id', $operation->variant_id);
            }
            $inventory = $inventoryQuery->orderBy('id')->first();
            if (! $inventory) return null;
            $operation->inventory_id = $inventory->id;
            return $operation;
        })->filter()
            ->groupBy(fn ($operation) => implode(':', [$operation->type, $operation->reference_id, $operation->inventory_id]))
            ->map(function ($group) {
                $operation = clone $group->first();
                $operation->quantity = $group->sum('quantity');
                return $operation;
            })
            ->sortByDesc(fn ($operation) => sprintf('%s-%010d', $operation->date, $operation->reference_id))
            ->values();

        $runningStock = [];
        foreach ($resolved as $operation) {
            $after = $runningStock[$operation->inventory_id]
                ?? (float) DB::table('inventory_items')->where('id', $operation->inventory_id)->value('current_stock');
            $before = $after - $operation->quantity;
            $date = $operation->date ?: now();
            $label = $operation->type === 'purchase' ? 'Compra' : 'Venta';
            $notes = $label.' '.($operation->number ?: '#'.$operation->reference_id);

            $existing = DB::table('stock_movements')
                ->where('inventory_item_id', $operation->inventory_id)
                ->where('movement_type', $operation->type)
                ->where('reference_type', $operation->reference_type)
                ->where('reference_id', $operation->reference_id)
                ->first();

            $values = [
                'quantity' => $operation->quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'updated_at' => $date,
            ];

            if ($existing) {
                DB::table('stock_movements')->where('id', $existing->id)->update($values);
            } else {
                DB::table('stock_movements')->insert($values + [
                    'inventory_item_id' => $operation->inventory_id,
                    'movement_type' => $operation->type,
                    'reference_type' => $operation->reference_type,
                    'reference_id' => $operation->reference_id,
                    'notes' => $notes.' (historial reconstruido)',
                    'created_at' => $date,
                ]);
            }

            $runningStock[$operation->inventory_id] = $before;
        }
    }

    public function down(): void
    {
        DB::table('stock_movements')
            ->where('movement_type', 'purchase')
            ->where('reference_type', 'purchase')
            ->where('notes', 'like', '%(historial reconstruido)')
            ->delete();
    }
};
