<?php

namespace App\Services\Reports\Stock;

use App\Models\Products\Brand;
use App\Models\Products\Color;
use App\Models\Products\Product;
use App\Models\Products\ProductCategory;
use App\Models\Products\ProductModel;
use App\Models\Products\Size;
use App\Models\Purchases\Provider;
use App\Models\Stock\InventoryItem;
use Illuminate\Support\Collection;

/** Construye el árbol valorizado de producto, color y talle desde las existencias actuales. */
class StockTreeReportService
{
    public function generate(array $filters, bool $paginate = true): array
    {
        $page = $paginate ? max(1, (int) ($filters['page'] ?? 1)) : 1;
        $perPage = $paginate ? (int) ($filters['per_page'] ?? 25) : 0;
        $productQuery = Product::query();

        if (! empty($filters['product'])) {
            $term = trim((string) $filters['product']);
            $productQuery->where(function ($query) use ($term) {
                $query->where('name', 'like', '%'.$term.'%')
                    ->orWhere('code', 'like', '%'.$term.'%');
            });
        }

        if (($filters['active'] ?? 'all') !== 'all') {
            $productQuery->where('is_active', $filters['active'] === 'active');
        }
        if (! empty($filters['brand_id'])) $productQuery->where('brand_id', $filters['brand_id']);
        if (! empty($filters['model_id'])) $productQuery->where('product_model_id', $filters['model_id']);
        if (! empty($filters['category_id'])) $productQuery->where('category_id', $filters['category_id']);
        if (! empty($filters['provider'])) $productQuery->where('principal_provider_name', 'like', '%'.trim((string) $filters['provider']).'%');

        $inventoryQuery = InventoryItem::query()->with(['variant.size', 'variant.color']);
        if (! empty($filters['warehouse'])) $inventoryQuery->where('warehouse_name', $filters['warehouse']);
        if (! empty($filters['currency'])) $inventoryQuery->where(fn ($q) => $q->where('currency_symbol', $filters['currency'])->orWhere('currency_id', $filters['currency']));
        if (! empty($filters['reference_code'])) $inventoryQuery->where('reference_code', 'like', '%'.$filters['reference_code'].'%');
        if (! empty($filters['last_movement_from'])) $inventoryQuery->whereHas('stockMovements', fn ($q) => $q->whereDate('created_at', '>=', $filters['last_movement_from']));
        if (! empty($filters['by_dispatch'])) $inventoryQuery->where(fn ($q) => $q->where('stock_batch', 'like', '%despach%')->orWhere('warehouse_name', 'like', '%despach%'));
        if (! empty($filters['variants_with_stock'])) $inventoryQuery->where('current_stock', '!=', 0);
        if (! empty($filters['size_id'])) $inventoryQuery->whereHas('variant', fn ($q) => $q->where('size_id', $filters['size_id']));
        if (! empty($filters['color_id'])) $inventoryQuery->whereHas('variant', fn ($q) => $q->where('color_id', $filters['color_id']));
        if (! empty($filters['size_text'])) {
            $sizeText = trim((string) $filters['size_text']);
            $inventoryQuery->where(fn ($q) => $q->where('size_name', 'like', '%'.$sizeText.'%')
                ->orWhereHas('variant.size', fn ($size) => $size->where('name', 'like', '%'.$sizeText.'%')));
        }

        if ($paginate) {
            $inventoryProductIds = (clone $inventoryQuery)
                ->whereNotNull('product_id')
                ->distinct()
                ->pluck('product_id');
            $productQuery->whereIn('id', $inventoryProductIds);
        }

        $total = $paginate ? (clone $productQuery)->count() : 0;
        $products = $productQuery
            ->with(['category:id,name', 'brand:id,name', 'model:id,name', 'variants.size', 'variants.color'])
            ->when($paginate, fn ($query) => $query->orderBy('name')->forPage($page, $perPage))
            ->get();

        if ($products->isEmpty()) {
            return $this->emptyReport($filters, $paginate);
        }
        $maps = [
            'id' => $products->keyBy('id'),
            'external' => $products->whereNotNull('external_id')->keyBy(fn ($p) => (string) $p->external_id),
            'code' => $products->whereNotNull('code')->keyBy(fn ($p) => mb_strtolower((string) $p->code)),
            'name' => $products->keyBy(fn ($p) => mb_strtolower((string) $p->name)),
        ];

        if ($paginate) {
            $inventoryQuery->whereIn('product_id', $products->pluck('id'));
        }

        $providerNames = ! empty($filters['provider']) ? mb_strtolower($filters['provider']) : null;
        $records = $inventoryQuery->get()->map(function ($item) use ($maps) {
            $product = $maps['id']->get($item->product_id)
                ?? $maps['external']->get((string) $item->product_external_id)
                ?? $maps['code']->get(mb_strtolower((string) $item->code))
                ?? $maps['name']->get(mb_strtolower((string) $item->product_name));

            // Los registros de inventario anteriores a product_variant_id guardaban
            // solamente los nombres de color y talle. Vinculamos la variante en
            // memoria para obtener su precio propio al valorizar el stock.
            if ($product && ! $item->variant) {
                $variant = $this->matchingVariant($product, $item);

                if ($variant) {
                    $item->product_variant_id = $variant->id;
                    $item->setRelation('variant', $variant);
                }
            }

            return compact('item', 'product');
        });

        if (! empty($filters['variants_with_stock']) && empty($filters['last_movement_from']) && empty($filters['by_dispatch'])) {
            $supplemental = collect();
            $records->filter(fn ($record) => $record['product'])->groupBy(fn ($record) => $record['product']->id)->each(function (Collection $group) use ($supplemental) {
                $product = $group->first()['product'];
                if ($group->contains(fn ($record) => ! $record['item']->product_variant_id)) return;
                $existing = $group->pluck('item.product_variant_id')->filter()->map(fn ($id) => (int) $id);
                $location = $group->first()['item'];
                foreach ($product->variants as $variant) {
                    if ($existing->contains((int) $variant->id)) continue;
                    $item = $location->replicate();
                    $item->id = null;
                    $item->product_id = $product->id;
                    $item->product_variant_id = $variant->id;
                    $item->current_stock = $variant->current_stock;
                    $item->size_name = $variant->size?->name;
                    $item->color_name = $variant->color?->name;
                    $item->bar_code = $variant->bar_code ?: $location->bar_code;
                    $item->setRelation('variant', $variant);
                    $supplemental->push(compact('item', 'product'));
                }
            });
            $records = $records->concat($supplemental);
        }

        $records = $records->filter(function ($record) use ($filters, $providerNames) {
            $item = $record['item']; $product = $record['product'];
            if (! $product) return empty($filters['product']) && empty($filters['brand_id']) && empty($filters['model_id']) && empty($filters['category_id']) && ! $providerNames;
            if (! empty($filters['product'])) { $term = mb_strtolower($filters['product']); if (! str_contains(mb_strtolower($product->name), $term) && ! str_contains(mb_strtolower((string) $product->code), $term)) return false; }
            if (($filters['active'] ?? 'all') !== 'all' && (bool) $product->is_active !== ($filters['active'] === 'active')) return false;
            if (! empty($filters['brand_id']) && (int) $product->brand_id !== (int) $filters['brand_id']) return false;
            if (! empty($filters['model_id']) && (int) $product->product_model_id !== (int) $filters['model_id']) return false;
            if (! empty($filters['category_id']) && (int) $product->category_id !== (int) $filters['category_id']) return false;
            if ($providerNames && ! str_contains(mb_strtolower((string) $product->principal_provider_name), $providerNames)) return false;
            $size = mb_strtolower((string) ($item->size_name ?: $item->variant?->size?->name));
            if (! empty($filters['size_id']) && (int) $item->variant?->size_id !== (int) $filters['size_id']) return false;
            if (! empty($filters['size_text']) && ! str_contains($size, mb_strtolower($filters['size_text']))) return false;
            if (! empty($filters['color_id']) && (int) $item->variant?->color_id !== (int) $filters['color_id']) return false;
            if (! empty($filters['variants_with_stock']) && (float) $item->current_stock == 0.0) return false;
            return true;
        });

        $columns = $records->map(fn ($r) => $this->column($r['item'], (bool) $filters['physical_warehouses']))->unique('key')->sortBy('label')->values();
        $mainCurrency = $records->pluck('item.currency_symbol')->filter()->countBy()->sortDesc()->keys()->first() ?: '$';
        $tree = $records->groupBy(fn ($r) => $this->productKey($r['product'], $r['item']))->map(function (Collection $group) use ($columns, $filters, $mainCurrency) {
            $first = $group->first(); $product = $first['product']; $item = $first['item'];
            $hasVariants = $group->contains(fn ($r) => $r['item']->product_variant_id || $r['item']->color_name || $r['item']->size_name);
            $colors = $hasVariants ? $group->groupBy(fn ($r) => $r['item']->color_name ?: $r['item']->variant?->color?->name ?: 'Sin color')->map(function (Collection $colorGroup, string $color) use ($columns, $filters, $mainCurrency) {
                $sizes = $colorGroup->groupBy(fn ($r) => $r['item']->size_name ?: $r['item']->variant?->size?->name ?: 'Sin talle')->map(fn (Collection $sizeGroup, string $size) => [
                    'key' => md5($color.'|'.$size), 'label' => $size, 'cells' => $this->cells($sizeGroup, $columns, $filters, $mainCurrency),
                ])->sortBy('label')->values();
                return ['key' => md5($color), 'label' => $color, 'cells' => $this->cells($colorGroup, $columns, $filters, $mainCurrency), 'sizes' => $sizes->all()];
            })->sortBy('label')->values() : collect();
            return [
                'key' => $this->productKey($product, $item), 'code' => $product?->code ?: $item->code ?: '-', 'name' => $product?->name ?: $item->product_name,
                'active' => (bool) ($product?->is_active ?? true), 'min_stock' => (float) ($group->max(fn ($r) => (float) $r['item']->min_stock) ?: $product?->min_stock ?: 0),
                'reposition_stock' => (float) ($group->max(fn ($r) => (float) $r['item']->reposition_stock) ?: $product?->reposition_stock ?: 0),
                'cells' => $this->cells($group, $columns, $filters, $mainCurrency), 'colors' => $colors->all(),
            ];
        })->sortBy('name')->values();

        if (! $paginate) {
            $total = $tree->count();
            $perPage = max(1, $total);
        }
        return ['columns' => $columns->all(), 'rows' => $tree->values()->all(),
            'pagination' => ['current_page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => max(1, (int) ceil($total / $perPage)), 'from' => $total ? (($page - 1) * $perPage) + 1 : 0, 'to' => min($page * $perPage, $total)],
            'context' => ['main_currency' => $mainCurrency, 'origin_currency' => (bool) $filters['origin_currency'], 'show_variants' => (bool) $filters['variants_with_stock'], 'show_thresholds' => (bool) $filters['show_thresholds']]];
    }

    public function options(): array
    {
        return ['products' => collect(), 'sizes' => Size::orderBy('name')->get(['id', 'name']), 'colors' => Color::orderBy('name')->get(['id', 'name']),
            'warehouses' => InventoryItem::whereNotNull('warehouse_name')->distinct()->orderBy('warehouse_name')->pluck('warehouse_name'),
            'currencies' => Product::whereNotNull('currency_symbol')->distinct()->orderBy('currency_symbol')->pluck('currency_symbol')->merge(InventoryItem::whereNotNull('currency_symbol')->distinct()->pluck('currency_symbol'))->filter()->unique()->values(),
            'brands' => Brand::orderBy('name')->get(['id', 'name']), 'models' => ProductModel::orderBy('name')->get(['id', 'name']),
            'categories' => ProductCategory::orderBy('name')->get(['id', 'name']), 'providers' => Provider::orderBy('name')->pluck('name')];
    }

    private function emptyReport(array $filters, bool $paginate): array
    {
        $page = $paginate ? max(1, (int) ($filters['page'] ?? 1)) : 1;
        $perPage = $paginate ? (int) ($filters['per_page'] ?? 25) : 1;

        return [
            'columns' => [],
            'rows' => [],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => 0,
                'last_page' => 1,
                'from' => 0,
                'to' => 0,
            ],
            'context' => [
                'main_currency' => '$',
                'origin_currency' => (bool) ($filters['origin_currency'] ?? false),
                'show_variants' => (bool) ($filters['variants_with_stock'] ?? false),
                'show_thresholds' => (bool) ($filters['show_thresholds'] ?? false),
            ],
        ];
    }

    private function cells(Collection $records, Collection $columns, array $filters, string $mainCurrency): array
    {
        $result = [];
        foreach ($columns as $column) {
            $matches = $records->filter(fn ($r) => $this->column($r['item'], (bool) $filters['physical_warehouses'])['key'] === $column['key']);
            $units = (float) $matches->sum(fn ($r) => (float) $r['item']->current_stock);
            $valued = (float) $matches->sum(fn ($r) => (float) $r['item']->current_stock * $this->cost($r['product'], $r['item']));
            $symbol = $filters['origin_currency'] ? ($matches->pluck('item.currency_symbol')->filter()->unique()->implode('/') ?: $matches->pluck('product.currency_symbol')->filter()->unique()->implode('/') ?: $mainCurrency) : $mainCurrency;
            $result[$column['key']] = ['units' => round($units, 4), 'valued' => round($valued, 2), 'currency' => $symbol];
        }
        $result['grand_total'] = ['units' => round((float) collect($result)->sum('units'), 4), 'valued' => round((float) collect($result)->sum('valued'), 2), 'currency' => $filters['origin_currency'] ? 'Origen' : $mainCurrency];
        return $result;
    }

    private function column(InventoryItem $item, bool $physical): array
    {
        $label = $physical ? collect([$item->branch_name, $item->warehouse_name])->filter()->implode(' · ') : ($item->branch_name ?: 'Sin sucursal');
        return ['key' => md5($label), 'label' => $label];
    }

    private function cost(?Product $product, InventoryItem $item): float
    {
        return (float) (
            $item->variant?->price_a_with_tax
            ?: $item->valued_item
            ?: $product?->cost_with_discount
            ?: $product?->replacement_cost
            ?: $product?->last_purchase_price
            ?: 0
        );
    }

    private function matchingVariant(Product $product, InventoryItem $item): mixed
    {
        $color = $this->normalized($item->color_name);
        $size = $this->normalized($item->size_name);

        if ($color === '' && $size === '') {
            return null;
        }

        return $product->variants->first(function ($variant) use ($color, $size) {
            $sameColor = $color === '' || $this->normalized($variant->color?->name) === $color;
            $sameSize = $size === '' || $this->normalized($variant->size?->name) === $size;

            return $sameColor && $sameSize;
        });
    }

    private function normalized(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    private function productKey(?Product $product, InventoryItem $item): string { return (string) ($product?->id ?: $item->product_external_id ?: $item->code ?: $item->product_name); }
}
