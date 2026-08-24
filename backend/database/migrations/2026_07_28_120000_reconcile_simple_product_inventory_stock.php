<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')
            || ! Schema::hasTable('product_variants')
            || ! Schema::hasTable('inventory_items')) {
            return;
        }

        DB::transaction(function () {
            $defaultLocation = DB::table('inventory_items')
                ->selectRaw('branch_name, warehouse_name, COUNT(*) as items_count')
                ->whereNotNull('branch_name')
                ->whereNotNull('warehouse_name')
                ->groupBy('branch_name', 'warehouse_name')
                ->orderByDesc('items_count')
                ->first();

            if (! $defaultLocation && Schema::hasTable('warehouses') && Schema::hasTable('branches')) {
                $defaultLocation = DB::table('warehouses')
                    ->join('branches', 'branches.id', '=', 'warehouses.branch_id')
                    ->where('warehouses.is_active', true)
                    ->where('branches.is_active', true)
                    ->orderByRaw("CASE WHEN UPPER(warehouses.name) LIKE '%PRINCIPAL%' THEN 0 ELSE 1 END")
                    ->orderBy('warehouses.id')
                    ->selectRaw('branches.name as branch_name, warehouses.name as warehouse_name')
                    ->first();
            }

            DB::table('products')
                ->where('has_variants', false)
                ->orderBy('id')
                ->get()
                ->each(function ($product) use ($defaultLocation) {
                    $variants = DB::table('product_variants')
                        ->where('product_id', $product->id)
                        ->orderBy('id')
                        ->get();
                    $initialStock = (float) ($variants->first()->current_stock ?? $product->current_stock ?? 0);

                    if ($variants->isEmpty()) {
                        $variantId = DB::table('product_variants')->insertGetId([
                            'product_id' => $product->id,
                            'sku' => $product->code ?: $product->reference_code,
                            'bar_code' => $product->bar_code,
                            'price_a_with_tax' => $product->price_a_with_tax,
                            'current_stock' => $product->current_stock ?? 0,
                            'available_stock' => $product->current_stock ?? 0,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } elseif ($variants->count() === 1) {
                        $variantId = $variants->first()->id;
                    } else {
                        return;
                    }

                    $unassignedItems = DB::table('inventory_items')
                        ->where('product_id', $product->id)
                        ->whereNull('product_variant_id')
                        ->get();

                    foreach ($unassignedItems as $item) {
                        $assigned = DB::table('inventory_items')
                            ->where('product_id', $product->id)
                            ->where('product_variant_id', $variantId)
                            ->where('branch_name', $item->branch_name)
                            ->where('warehouse_name', $item->warehouse_name)
                            ->first();

                        if ($assigned) {
                            DB::table('inventory_items')->where('id', $assigned->id)->update([
                                'current_stock' => (float) $assigned->current_stock + (float) $item->current_stock,
                                'updated_at' => now(),
                            ]);
                            DB::table('inventory_items')->where('id', $item->id)->delete();
                        } else {
                            DB::table('inventory_items')->where('id', $item->id)->update([
                                'product_variant_id' => $variantId,
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    $inventoryStock = (float) DB::table('inventory_items')
                        ->where('product_id', $product->id)
                        ->where('product_variant_id', $variantId)
                        ->sum('current_stock');

                    if ($inventoryStock == 0.0
                        && ! DB::table('inventory_items')->where('product_id', $product->id)->exists()
                        && $defaultLocation) {
                        if ($initialStock > 0) {
                            DB::table('inventory_items')->insert([
                                'product_id' => $product->id,
                                'product_variant_id' => $variantId,
                                'product_external_id' => $product->external_id,
                                'code' => $product->code,
                                'bar_code' => $product->bar_code,
                                'reference_code' => $product->reference_code,
                                'product_name' => $product->name,
                                'branch_name' => $defaultLocation->branch_name,
                                'warehouse_name' => $defaultLocation->warehouse_name,
                                'current_stock' => $initialStock,
                                'currency_symbol' => $product->currency_symbol ?: '$',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $inventoryStock = $initialStock;
                        }
                    }

                    DB::table('product_variants')->where('id', $variantId)->update([
                        'current_stock' => $inventoryStock,
                        'available_stock' => $inventoryStock,
                        'updated_at' => now(),
                    ]);

                    DB::table('products')->where('id', $product->id)->update([
                        'current_stock' => $inventoryStock,
                        'available_stock' => $inventoryStock,
                        'updated_at' => now(),
                    ]);
                });
        });
    }

    public function down(): void
    {
        // La conciliación corrige datos existentes y no se revierte para no perder stock.
    }
};
