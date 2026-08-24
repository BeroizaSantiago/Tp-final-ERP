<?php

namespace App\Services\Reports\Stock;

use App\Models\Products\Brand;
use App\Models\Products\Product;
use App\Models\Products\ProductCategory;
use App\Models\Products\ProductModel;
use App\Models\Purchases\Provider;
use App\Models\Stock\InventoryItem;
use App\Services\Reports\Sales\DetailedSalesReportService;
use Illuminate\Support\Collection;

/** Relaciona las existencias actuales con las ventas netas de un período. */
class StockBySalesReportService
{
    public function __construct(private readonly DetailedSalesReportService $sales) {}

    public function generate(array $filters, bool $paginate = true): array
    {
        $products = Product::with(['category:id,name', 'brand:id,name', 'model:id,name'])->get();
        $byId = $products->keyBy('id');
        $byExternal = $products->whereNotNull('external_id')->keyBy(fn ($p) => (string) $p->external_id);
        $byName = $products->keyBy(fn ($p) => mb_strtolower((string) $p->name));
        $providerNames = ! empty($filters['provider_ids']) ? Provider::whereIn('id', $filters['provider_ids'])->pluck('name')->map(fn ($name) => mb_strtolower($name)) : collect();

        $inventory = InventoryItem::with(['variant.size', 'variant.color'])
            ->when($filters['warehouse'] ?? null, fn ($query, $warehouse) => $query->where('warehouse_name', $warehouse))
            ->get()->map(function ($item) use ($byId, $byExternal, $byName) {
                $product = $byId->get($item->product_id) ?? $byExternal->get((string) $item->product_external_id) ?? $byName->get(mb_strtolower((string) $item->product_name));
                return compact('item', 'product');
            })->filter(fn ($record) => $this->matches($record['product'], $filters, $providerNames));

        $sales = collect($this->sales->generate(['date_from' => $filters['date_from'], 'date_to' => $filters['date_to'], 'with_variant' => true])['rows']);
        $byVariant = $sales->filter(fn ($row) => $row['variant_id'])->groupBy(fn ($row) => (string) $row['variant_id'])->map->sum('quantity');
        $byProduct = $sales->filter(fn ($row) => $row['product_id'] && ! $row['variant_id'])->groupBy(fn ($row) => (string) $row['product_id'])->map->sum('quantity');
        $bySignature = $sales->groupBy(fn ($row) => $this->signature($row['product'], $row['size'], $row['color']))->map->sum('quantity');

        $variants = $inventory->map(function ($record) use ($byVariant, $byProduct, $bySignature) {
            $item = $record['item']; $product = $record['product'];
            $size = $item->size_name ?: $item->variant?->size?->name ?: 'Sin talle';
            $color = $item->color_name ?: $item->variant?->color?->name ?: 'Sin color';
            $sold = $item->product_variant_id
                ? ($byVariant->get((string) $item->product_variant_id) ?? $bySignature->get($this->signature($product?->name ?: $item->product_name, $size, $color), 0))
                : ($product?->id ? $byProduct->get((string) $product->id, 0) : $bySignature->get($this->signature($item->product_name, $size, $color), 0));
            return [
                'product_key' => (string) ($product?->id ?: $item->product_external_id ?: $item->product_name),
                'variant_key' => (string) ($item->product_variant_id ?: $size.'|'.$color),
                'code' => $product?->code ?: $item->code ?: '-', 'product' => $product?->name ?: $item->product_name,
                'variant' => collect([$size, $color])->reject(fn ($value) => str_starts_with($value, 'Sin '))->implode(' · ') ?: 'Sin variante',
                'color' => $color, 'size' => $size, 'warehouse' => $item->warehouse_name ?: 'Sin depósito',
                'branch' => $item->branch_name ?: 'Sin sucursal', 'stock' => (float) $item->current_stock, 'sold' => (float) $sold,
            ];
        })->groupBy(fn ($row) => $row['product_key'].'|'.$row['variant_key'].(! empty($filters['warehouse']) ? '|'.$row['warehouse'].'|'.$row['branch'] : ''))->map(function ($group) use ($filters) {
            $row = $group->first();
            $row['stock'] = round((float) $group->sum('stock'), 4);
            if (empty($filters['warehouse'])) {
                $row['warehouse'] = 'Todos los depósitos';
                $row['branch'] = $group->pluck('branch')->unique()->sort()->implode(' / ');
            }
            return $row;
        })->values();

        $groups = $variants->groupBy('product_key')->map(function (Collection $rows) {
            $first = $rows->first();
            return ['key' => $first['product_key'], 'code' => $first['code'], 'product' => $first['product'],
                'stock' => round((float) $rows->sum('stock'), 4), 'sold' => round((float) $rows->sum('sold'), 4),
                'variants' => $rows->sortBy(['size', 'color'])->values()->all()];
        })->sortBy('product')->values();

        $total = $groups->count(); $page = $paginate ? max(1, (int) ($filters['page'] ?? 1)) : 1; $perPage = $paginate ? (int) ($filters['per_page'] ?? 25) : max(1, $total);
        return ['rows' => ($paginate ? $groups->forPage($page, $perPage) : $groups)->values()->all(), 'summary' => ['products' => $total, 'stock' => round((float) $groups->sum('stock'), 4), 'sold' => round((float) $groups->sum('sold'), 4)], 'context' => ['warehouse' => $filters['warehouse'] ?? 'Todos los depósitos'], 'pagination' => ['current_page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => max(1, (int) ceil($total / $perPage)), 'from' => $total ? (($page - 1) * $perPage) + 1 : 0, 'to' => min($page * $perPage, $total)]];
    }

    public function options(): array
    {
        return ['warehouses' => InventoryItem::whereNotNull('warehouse_name')->distinct()->orderBy('warehouse_name')->pluck('warehouse_name'),
            'products' => collect(), 'providers' => Provider::orderBy('name')->get(['id', 'name']),
            'categories' => ProductCategory::orderBy('name')->get(['id', 'name']), 'brands' => Brand::orderBy('name')->get(['id', 'name']), 'models' => ProductModel::orderBy('name')->get(['id', 'name'])];
    }

    private function matches(?Product $product, array $filters, Collection $providerNames): bool
    {
        if (! $product) return empty($filters['product_ids']) && empty($filters['provider_ids']) && empty($filters['category_ids']) && empty($filters['brand_ids']) && empty($filters['model_ids']);
        if (! empty($filters['product_ids']) && ! in_array($product->id, $filters['product_ids'])) return false;
        if (! empty($filters['name']) && ! str_contains(mb_strtolower($product->name), mb_strtolower($filters['name']))) return false;
        if (! empty($filters['provider_ids']) && ! $providerNames->contains(mb_strtolower((string) $product->principal_provider_name))) return false;
        if (! empty($filters['category_ids']) && ! in_array($product->category_id, $filters['category_ids'])) return false;
        if (! empty($filters['brand_ids']) && ! in_array($product->brand_id, $filters['brand_ids'])) return false;
        if (! empty($filters['model_ids']) && ! in_array($product->product_model_id, $filters['model_ids'])) return false;
        return true;
    }

    private function signature(string $product, string $size, string $color): string { return mb_strtolower(trim($product).'|'.trim($size).'|'.trim($color)); }
}
