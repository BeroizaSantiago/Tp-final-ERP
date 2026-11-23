<?php

namespace App\Services\Reports\Purchases;

use App\Models\Products\Author;
use App\Models\Products\Collection as BookCollection;
use App\Models\Products\ProductCategory;
use App\Models\Products\Publisher;
use App\Models\Purchases\Provider;
use App\Models\Purchases\Purchase;
use Illuminate\Support\Collection;

/** Obtiene una fila por cada libro incluido en comprobantes de compra. */
class DetailedPurchasesReportService
{
    public function generate(array $filters): array
    {
        $purchases = Purchase::query()
            ->with(['provider:id,name', 'items.product.author:id,name', 'items.product.publisher:id,name',
                'items.product.collection:id,name', 'items.product.category:id,name', 'items.variant:id,bar_code'])
            ->whereDate('issue_date', '>=', $filters['date_from'])->whereDate('issue_date', '<=', $filters['date_to'])
            ->whereRaw("LOWER(COALESCE(status_name,'')) NOT LIKE '%anul%'")
            ->when($filters['provider_ids'] ?? null, fn ($query, $ids) => $query->whereIn('provider_id', $ids))
            ->orderBy('issue_date')->get();

        $rows = $purchases->flatMap(function (Purchase $purchase) {
            $factor = $this->factor($purchase);
            return $purchase->items->map(function ($item) use ($purchase, $factor) {
                $product = $item->product;
                $quantity = (float) $item->quantity;
                $discount = $quantity * (float) $item->unit_price * (float) $item->discount_percentage / 100;
                $total = (float) $item->total_amount ?: (float) $item->subtotal_amount + (float) $item->tax_amount;
                return [
                    'type'=>$purchase->receipt_type_name ?: 'Compra', 'date'=>optional($purchase->issue_date)->format('d/m/Y'),
                    'number'=>$purchase->full_number ?: 'Compra #'.$purchase->id, 'product_id'=>$item->product_id,
                    'product'=>$item->product_name ?: $product?->name ?: 'Sin producto',
                    'provider'=>$purchase->provider?->name ?: $purchase->provider_name ?: 'Sin proveedor', 'branch'=>'Sin sucursal',
                    'code'=>$item->product_code ?: $product?->code ?: '—', 'barcode'=>$item->variant?->bar_code ?: $product?->bar_code ?: '—',
                    'category_id'=>$product?->category_id, 'author_id'=>$product?->author_id,
                    'publisher_id'=>$product?->publisher_id, 'collection_id'=>$product?->collection_id,
                    'category'=>$product?->getRelation('category')?->name ?: $product?->getAttribute('category') ?: 'Sin género',
                    'author'=>$product?->author?->name ?: 'Sin autor', 'publisher'=>$product?->publisher?->name ?: 'Sin editorial',
                    'collection'=>$product?->collection?->name ?: 'Sin colección',
                    'quantity'=>round($factor*$quantity, 4), 'unit_price'=>round((float)$item->unit_price, 2),
                    'discount'=>round($factor*$discount, 2), 'tax_percentage'=>(float)$item->tax_percentage,
                    'iva'=>round($factor*(float)$item->tax_amount, 2), 'total'=>round($factor*$total, 2),
                ];
            });
        })->when($filters['branches'] ?? null, fn (Collection $rows, $values) => $rows->whereIn('branch', $values))
            ->when($filters['product_id'] ?? null, fn (Collection $rows, $id) => $rows->where('product_id', (int)$id))
            ->when($filters['category_id'] ?? null, fn (Collection $rows, $id) => $rows->where('category_id', (int)$id))
            ->when($filters['author_id'] ?? null, fn (Collection $rows, $id) => $rows->where('author_id', (int)$id))
            ->when($filters['publisher_id'] ?? null, fn (Collection $rows, $id) => $rows->where('publisher_id', (int)$id))
            ->when($filters['collection_id'] ?? null, fn (Collection $rows, $id) => $rows->where('collection_id', (int)$id))->values();

        return ['filters'=>$filters, 'rows'=>$rows->all(), 'summary'=>[
            'quantity'=>round((float)$rows->sum('quantity'), 4), 'discount'=>round((float)$rows->sum('discount'), 2),
            'iva'=>round((float)$rows->sum('iva'), 2), 'total'=>round((float)$rows->sum('total'), 2), 'records'=>$rows->count(),
        ]];
    }

    public function options(): array
    {
        return ['branches'=>collect(['Sin sucursal']), 'providers'=>Provider::query()->orderBy('name')->get(['id','name']),
            'authors'=>Author::query()->where('is_active',true)->orderBy('name')->get(['id','name']),
            'publishers'=>Publisher::query()->where('is_active',true)->orderBy('name')->get(['id','name']),
            'collections'=>BookCollection::query()->where('is_active',true)->orderBy('name')->get(['id','name']),
            'categories'=>ProductCategory::query()->orderBy('name')->get(['id','name'])];
    }

    public function exportRows(array $report): Collection
    {
        return collect($report['rows'])->map(fn ($r) => [$r['type'],$r['date'],$r['number'],$r['product'],$r['author'],$r['publisher'],$r['category'],$r['collection'],$r['provider'],$r['code'],$r['barcode'],$r['quantity'],$r['unit_price'],$r['discount'],$r['tax_percentage'],$r['iva'],$r['total']]);
    }

    private function factor(Purchase $purchase): int
    {
        $name=mb_strtolower((string)$purchase->receipt_type_name);
        return $purchase->receipt_types_prefix==='NC'||str_contains($name,'crédito')||str_contains($name,'credito') ? -1 : 1;
    }
}
