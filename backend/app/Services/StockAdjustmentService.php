<?php

namespace App\Services;

use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use App\Models\Stock\InventoryItem;
use App\Models\Stock\StockAdjustment;
use App\Models\Stock\StockMovement;
use App\Models\Stock\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAdjustmentService
{
    public function __construct(private readonly StockService $stockService)
    {
    }

    public function create(array $data, ?string $userName = null): StockAdjustment
    {
        return DB::transaction(function () use ($data, $userName) {
            $adjustment = StockAdjustment::create([
                'number' => null,
                'warehouse_name' => $data['warehouse_name'],
                'branch_name' => $data['branch_name'],
                'created_by' => $userName ?: 'system',
                'quantity' => 0,
                'date' => $data['date'],
                'is_active' => true,
                'reason_id' => $data['reason_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $adjustment->update([
                'number' => str_pad((string) $adjustment->id, 8, '0', STR_PAD_LEFT),
            ]);

            $totalDifference = 0.0;
            $seen = [];

            foreach ($data['items'] as $index => $itemData) {
                $product = Product::query()->lockForUpdate()->findOrFail($itemData['product_id']);
                if (! $product->is_active) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => 'El producto está inhabilitado y no puede mover stock.',
                    ]);
                }
                $variant = $this->variant($product, $itemData, (bool) $data['adjust_by_variant'], $index);
                $duplicateKey = $product->id . ':' . ($variant?->id ?: 'product');

                if (isset($seen[$duplicateKey])) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => 'El producto y la variante ya fueron agregados al ajuste.',
                    ]);
                }
                $seen[$duplicateKey] = true;

                $inventoryItem = $this->inventoryItem(
                    $product,
                    $variant,
                    $data['branch_name'],
                    $data['warehouse_name']
                );

                $stockBefore = (float) $inventoryItem->current_stock;
                $stockAfter = (float) $itemData['quantity'];
                $difference = $stockAfter - $stockBefore;

                $inventoryItem->update(['current_stock' => $stockAfter]);

                if ($variant) {
                    $this->stockService->recalculateVariantStock($variant);
                    $this->stockService->recalculateProductStock($product);
                } else {
                    $productStock = (float) InventoryItem::query()
                        ->where('product_id', $product->id)
                        ->whereNull('product_variant_id')
                        ->sum('current_stock');
                    $product->update([
                        'current_stock' => $productStock,
                        'available_stock' => $productStock,
                    ]);
                }

                StockMovement::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'movement_type' => 'adjustment',
                    'quantity' => $difference,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'reference_type' => 'stock_adjustment',
                    'reference_id' => $adjustment->id,
                    'notes' => $data['notes'] ?? null,
                ]);

                $totalDifference += $difference;
            }

            $adjustment->update(['quantity' => $totalDifference]);

            return $adjustment->load([
                'reason',
                'movements.inventoryItem.product',
                'movements.inventoryItem.variant.size',
                'movements.inventoryItem.variant.color',
            ]);
        });
    }

    public function currentStock(
        int $productId,
        ?int $variantId,
        string $branchName,
        string $warehouseName
    ): float {
        if (! $variantId) {
            $product = Product::query()->find($productId);
            if ($product && ! $product->has_variants) {
                $variantId = $product->variants()->value('id');
            }
        }

        return (float) InventoryItem::query()
            ->where('product_id', $productId)
            ->where('branch_name', $branchName)
            ->where('warehouse_name', $warehouseName)
            ->when(
                $variantId,
                fn ($query) => $query->where('product_variant_id', $variantId),
                fn ($query) => $query->whereNull('product_variant_id')
            )
            ->value('current_stock');
    }

    public function locations(): array
    {
        $locations = Warehouse::query()
            ->with('branch:id,name')
            ->where('is_active', true)
            ->whereHas('branch', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get()
            ->map(fn (Warehouse $warehouse) => [
                'branch_id' => $warehouse->branch_id,
                'branch_name' => $warehouse->branch->name,
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->name,
            ])
            ->sortBy([
                ['branch_name', 'asc'],
                ['warehouse_name', 'asc'],
            ])
            ->values();

        return ['locations' => $locations];
    }

    private function variant(
        Product $product,
        array $itemData,
        bool $adjustByVariant,
        int $index
    ): ?ProductVariant {
        if (! $adjustByVariant) {
            $variants = $product->variants()->lockForUpdate()->get();

            if ($variants->count() > 1) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'El producto posee varias variantes. Activá "Ajuste por variante" para ajustar su stock.',
                ]);
            }

            // Algunos productos simples poseen una única variante técnica sin
            // talle/color. Se utiliza internamente aunque la grilla sea por producto.
            return $variants->first();
        }

        $variant = ProductVariant::query()
            ->lockForUpdate()
            ->find($itemData['product_variant_id'] ?? null);

        if (! $variant || (int) $variant->product_id !== (int) $product->id) {
            throw ValidationException::withMessages([
                "items.{$index}.product_variant_id" => 'La variante seleccionada no pertenece al producto.',
            ]);
        }

        return $variant;
    }

    private function inventoryItem(
        Product $product,
        ?ProductVariant $variant,
        string $branchName,
        string $warehouseName
    ): InventoryItem {
        $inventoryItem = InventoryItem::query()
            ->where('product_id', $product->id)
            ->where('branch_name', $branchName)
            ->where('warehouse_name', $warehouseName)
            ->when(
                $variant,
                fn ($query) => $query->where('product_variant_id', $variant->id),
                fn ($query) => $query->whereNull('product_variant_id')
            )
            ->lockForUpdate()
            ->first();

        if ($inventoryItem) {
            return $inventoryItem;
        }

        $variant?->loadMissing(['size', 'color']);

        return InventoryItem::create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'product_external_id' => $product->external_id,
            'product_variant_external_id' => $variant?->external_id,
            'code' => $product->code,
            'bar_code' => $variant?->bar_code ?: $product->bar_code,
            'reference_code' => $product->reference_code,
            'product_name' => $product->name,
            'branch_name' => $branchName,
            'warehouse_name' => $warehouseName,
            'current_stock' => 0,
            'color_name' => $variant?->color?->name,
            'size_name' => $variant?->size?->name,
            'currency_symbol' => $product->currency_symbol ?: '$',
        ]);
    }
}
