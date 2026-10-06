<?php

namespace App\Services;

use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use App\Models\Stock\InventoryItem;
use App\Models\Stock\Warehouse;
use Illuminate\Validation\ValidationException;

class StockService
{
    /**
     * Garantiza la variante técnica utilizada por los productos simples.
     * También vincula inventario legado que todavía no tenía variante.
     */
    public function ensureTechnicalVariant(Product $product): ProductVariant
    {
        $variant = $product->variants()->orderBy('id')->first();

        if (! $variant) {
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $product->code ?: $product->reference_code,
                'bar_code' => $product->bar_code,
                'price_a_with_tax' => $product->price_a_with_tax,
                'current_stock' => 0,
                'available_stock' => 0,
                'is_active' => true,
            ]);
        } else {
            $variant->update([
                'sku' => $variant->sku ?: ($product->code ?: $product->reference_code),
                'bar_code' => $variant->bar_code ?: $product->bar_code,
                'price_a_with_tax' => $product->price_a_with_tax,
                'is_active' => true,
            ]);
        }

        InventoryItem::query()
            ->where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->update(['product_variant_id' => $variant->id]);

        if ($variant->inventoryItems()->exists()) {
            $this->recalculateVariantStock($variant);
        }

        return $variant->fresh();
    }

    public function recalculateProductStock(Product $product): void
    {
        $totalStock = $product->variants()->sum('current_stock');

        $product->update([
            'current_stock' => $totalStock,
            'available_stock' => $totalStock,
        ]);
    }

    public function recalculateVariantStock(ProductVariant $variant): float
    {
        $totalStock = (float) InventoryItem::query()
            ->where('product_variant_id', $variant->id)
            ->sum('current_stock');

        $variant->update([
            'current_stock' => $totalStock,
            'available_stock' => $totalStock,
        ]);

        return $totalStock;
    }

    public function initializeVariantStock(
        Product $product,
        ProductVariant $variant,
        float $stock
    ): ?InventoryItem {
        if (! $product->is_active) {
            throw ValidationException::withMessages([
                'product_id' => 'El producto está inhabilitado y no puede mover stock.',
            ]);
        }

        if ($stock <= 0) {
            return null;
        }

        $location = $this->defaultLocation();
        if (! $location) {
            return null;
        }

        $variant->loadMissing(['category', 'brand', 'publisher', 'model', 'collection']);

        return InventoryItem::updateOrCreate([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'branch_name' => $location['branch_name'],
            'warehouse_name' => $location['warehouse_name'],
        ], [
            'product_external_id' => $product->external_id,
            'product_variant_external_id' => $variant->external_id,
            'code' => $variant->sku ?: $product->code,
            'bar_code' => $variant->bar_code ?: $product->bar_code,
            'reference_code' => $product->reference_code,
            'product_name' => $product->name,
            'current_stock' => $stock,
            'color_name' => $variant->category?->name,
            'size_name' => $variant->publisher?->name,
            'currency_symbol' => $product->currency_symbol ?: '$',
        ]);
    }

    public function setVariantStockAtDefaultLocation(
        Product $product,
        ProductVariant $variant,
        float $stock
    ): void {
        if (! $product->is_active) {
            throw ValidationException::withMessages([
                'product_id' => 'El producto está inhabilitado y no puede mover stock.',
            ]);
        }

        $location = $this->defaultLocation();
        if (! $location) {
            $variant->update([
                'current_stock' => $stock,
                'available_stock' => $stock,
            ]);
            $this->recalculateProductStock($product);
            return;
        }

        $variant->loadMissing(['category', 'brand', 'publisher', 'model', 'collection']);

        InventoryItem::updateOrCreate([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'branch_name' => $location['branch_name'],
            'warehouse_name' => $location['warehouse_name'],
        ], [
            'product_external_id' => $product->external_id,
            'product_variant_external_id' => $variant->external_id,
            'code' => $variant->sku ?: $product->code,
            'bar_code' => $variant->bar_code ?: $product->bar_code,
            'reference_code' => $product->reference_code,
            'product_name' => $product->name,
            'current_stock' => $stock,
            'color_name' => $variant->category?->name,
            'size_name' => $variant->publisher?->name,
            'currency_symbol' => $product->currency_symbol ?: '$',
        ]);

        $this->recalculateVariantStock($variant);
        $this->recalculateProductStock($product);
    }

    private function defaultLocation(): ?array
    {
        $mostUsed = InventoryItem::query()
            ->selectRaw('branch_name, warehouse_name, COUNT(*) as items_count')
            ->whereNotNull('branch_name')
            ->whereNotNull('warehouse_name')
            ->groupBy('branch_name', 'warehouse_name')
            ->orderByDesc('items_count')
            ->first();

        if ($mostUsed) {
            $exists = Warehouse::query()
                ->where('name', $mostUsed->warehouse_name)
                ->where('is_active', true)
                ->whereHas('branch', fn ($query) => $query
                    ->where('name', $mostUsed->branch_name)
                    ->where('is_active', true))
                ->exists();

            if ($exists) {
                return [
                    'branch_name' => $mostUsed->branch_name,
                    'warehouse_name' => $mostUsed->warehouse_name,
                ];
            }
        }

        $warehouse = Warehouse::query()
            ->with('branch:id,name')
            ->where('is_active', true)
            ->whereHas('branch', fn ($query) => $query->where('is_active', true))
            ->orderByRaw("CASE WHEN UPPER(name) LIKE '%PRINCIPAL%' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        return $warehouse ? [
            'branch_name' => $warehouse->branch->name,
            'warehouse_name' => $warehouse->name,
        ] : null;
    }
}
