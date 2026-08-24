<?php

namespace App\Services\Reports\Stock;

use App\Models\Products\Brand;
use App\Models\Products\Product;
use App\Models\Products\ProductCategory;
use App\Models\Products\ProductModel;
use App\Models\Purchases\Provider;
use App\Models\Stock\InventoryItem;
use Illuminate\Support\Collection;

/** Detecta faltantes por sucursal y depósito usando los umbrales configurados. */
class StockReplenishmentReportService
{
    public function generate(array $filters): array
    {
        $products = Product::with(['category:id,name', 'brand:id,name', 'model:id,name'])->get();
        $context = [
            'id' => $products->keyBy('id'),
            'external' => $products->whereNotNull('external_id')->keyBy(fn ($product) => (string) $product->external_id),
            'code' => $products->whereNotNull('code')->keyBy(fn ($product) => (string) $product->code),
            'name' => $products->keyBy(fn ($product) => mb_strtolower($product->name)),
        ];

        $records = $this->inventory($filters, $products)->get()->map(function ($item) use ($context, $filters) {
            $product = $context['id']->get($item->product_id)
                ?? $context['external']->get((string) $item->product_external_id)
                ?? $context['code']->get((string) $item->code)
                ?? $context['name']->get(mb_strtolower($item->product_name));

            if (! $this->matchesProductFilters($product, $filters)) {
                return null;
            }

            $field = $filters['criterion'] === 'minimum' ? 'min_stock' : 'reposition_stock';
            $target = (float) $item->{$field};
            if ($target <= 0) {
                $target = (float) ($product?->{$field} ?? 0);
            }
            if ($target <= 0) {
                return null;
            }

            return [
                'key' => (string) ($product?->id ?: $item->product_external_id ?: $item->code ?: $item->product_name),
                'code' => $product?->code ?: $item->code ?: '-',
                'bar_code' => $item->bar_code ?: $product?->bar_code ?: '-',
                'product' => $product?->name ?: $item->product_name,
                'size' => $item->size_name ?: $item->variant?->size?->name ?: '-',
                'color' => $item->color_name ?: $item->variant?->color?->name ?: '-',
                'target' => $target,
                'current' => (float) $item->current_stock,
                'branch' => $item->branch_name ?: '-',
                'warehouse' => $item->warehouse_name ?: '-',
                'provider' => $product?->principal_provider_name ?: '-',
                'category' => $product?->category?->name ?: $product?->getRawOriginal('category') ?: '-',
                'brand' => $product?->brand?->name ?: $product?->getRawOriginal('brand') ?: '-',
                'model' => $product?->model?->name ?: $product?->getRawOriginal('model') ?: '-',
                'suggested' => 0,
            ];
        })->filter();

        $records = $records->groupBy(fn ($row) => $row['key'].'|'.($filters['include_variants'] ? $row['size'].'|'.$row['color'].'|' : '').$row['branch'].'|'.$row['warehouse'])
            ->map(function ($group) use ($filters) {
                $row = $group->first();
                if (! $filters['include_variants']) {
                    $row['size'] = 'Todas';
                    $row['color'] = 'Todos';
                }
                $row['current'] = round((float) $group->sum('current'), 4);
                $row['target'] = round((float) $group->max('target'), 4);
                $row['suggested'] = max(0, $row['target'] - $row['current']);

                return $row;
            })->filter(fn ($row) => $row['current'] <= $row['target']);

        $rows = $records->sortBy([
            fn ($a, $b) => $b['suggested'] <=> $a['suggested'],
            fn ($a, $b) => strcmp($a['product'], $b['product']),
            fn ($a, $b) => strcmp($a['size'], $b['size']),
            fn ($a, $b) => strcmp($a['color'], $b['color']),
        ])->values();

        return [
            'filters' => $filters,
            'criterion_label' => $filters['criterion'] === 'minimum' ? 'Stock Mínimo' : 'Stock de Reposición',
            'rows' => $rows->map(fn ($row) => collect($row)->except('key')->all())->all(),
            'summary' => [
                'products' => $rows->count(),
                'current' => round((float) $rows->sum('current'), 4),
                'suggested' => round((float) $rows->sum('suggested'), 4),
            ],
        ];
    }

    public function options(): array
    {
        return [
            'products' => collect(),
            'branches' => InventoryItem::whereNotNull('branch_name')->distinct()->orderBy('branch_name')->pluck('branch_name'),
            'warehouses' => InventoryItem::whereNotNull('warehouse_name')->distinct()->orderBy('warehouse_name')->pluck('warehouse_name'),
            'providers' => Provider::orderBy('name')->get(['id', 'name']),
            'categories' => ProductCategory::orderBy('name')->get(['id', 'name']),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'models' => ProductModel::orderBy('name')->get(['id', 'name']),
        ];
    }

    public function exportRows(array $report): Collection
    {
        return collect($report['rows'])->map(fn ($row) => [$row['code'], $row['bar_code'], $row['product'], $row['size'], $row['color'], $row['target'], $row['current'], $row['branch'], $row['warehouse'], $row['provider'], $row['category'], $row['brand'], $row['model'], $row['suggested']]);
    }

    private function inventory(array $filters, Collection $products)
    {
        $query = InventoryItem::query()->with(['variant.size', 'variant.color'])
            ->where('branch_name', $filters['branch'])
            ->when($filters['warehouses'] ?? null, fn ($query, $values) => $query->whereIn('warehouse_name', $values));

        if ($ids = $filters['product_ids'] ?? null) {
            $selected = $products->whereIn('id', $ids);
            $query->where(fn ($inventory) => $inventory->whereIn('product_id', $ids)
                ->orWhereIn('product_external_id', $selected->pluck('external_id')->filter())
                ->orWhereIn('code', $selected->pluck('code')->filter())
                ->orWhereIn('product_name', $selected->pluck('name')));
        }

        return $query;
    }

    private function matchesProductFilters($product, array $filters): bool
    {
        if (! $product) {
            return empty($filters['provider_ids']) && empty($filters['category_ids']) && empty($filters['brand_ids']) && empty($filters['model_ids']);
        }
        if (! empty($filters['provider_ids'])) {
            $providerNames = Provider::whereIn('id', $filters['provider_ids'])->pluck('name');
            if (! $providerNames->contains($product->principal_provider_name)) {
                return false;
            }
        }
        if (! empty($filters['category_ids']) && ! in_array($product->category_id, $filters['category_ids'])) return false;
        if (! empty($filters['brand_ids']) && ! in_array($product->brand_id, $filters['brand_ids'])) return false;
        if (! empty($filters['model_ids']) && ! in_array($product->product_model_id, $filters['model_ids'])) return false;

        return true;
    }
}
