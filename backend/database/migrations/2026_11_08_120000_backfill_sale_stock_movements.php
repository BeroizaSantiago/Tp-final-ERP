<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reconstruye únicamente la bitácora de ventas finalizadas anteriores a la
     * incorporación de StockMovement. No modifica ninguna existencia.
     */
    public function up(): void
    {
        $sales = DB::table('invoice_items as item')
            ->join('invoices as invoice', 'invoice.id', '=', 'item.invoice_id')
            ->whereNotNull('invoice.stock_applied_at')
            ->whereNotNull('item.product_variant_id')
            ->selectRaw('item.invoice_id, item.product_variant_id, SUM(item.quantity) as quantity, MAX(item.product_name) as product_name, MAX(invoice.full_number) as full_number, MAX(invoice.stock_applied_at) as movement_date')
            ->groupBy('item.invoice_id', 'item.product_variant_id')
            ->orderByDesc('movement_date')
            ->orderByDesc('item.invoice_id')
            ->get();

        $runningStock = [];

        foreach ($sales as $sale) {
            $inventory = DB::table('inventory_items')
                ->where('product_variant_id', $sale->product_variant_id)
                ->orderBy('id')
                ->first();

            if (! $inventory) {
                continue;
            }

            $alreadyRecorded = DB::table('stock_movements')
                ->where('inventory_item_id', $inventory->id)
                ->where('movement_type', 'sale')
                ->where('reference_type', 'invoice')
                ->where('reference_id', $sale->invoice_id)
                ->exists();

            if ($alreadyRecorded) {
                continue;
            }

            $after = $runningStock[$inventory->id] ?? (float) $inventory->current_stock;
            $quantity = (float) $sale->quantity;
            $before = $after + $quantity;
            $date = $sale->movement_date ?: now();

            DB::table('stock_movements')->insert([
                'inventory_item_id' => $inventory->id,
                'movement_type' => 'sale',
                'quantity' => -$quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'reference_type' => 'invoice',
                'reference_id' => $sale->invoice_id,
                'notes' => 'Venta '.($sale->full_number ?: '#'.$sale->invoice_id).' (historial reconstruido)',
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            $runningStock[$inventory->id] = $before;
        }
    }

    public function down(): void
    {
        DB::table('stock_movements')
            ->where('movement_type', 'sale')
            ->where('reference_type', 'invoice')
            ->where('notes', 'like', '%(historial reconstruido)')
            ->delete();
    }
};
