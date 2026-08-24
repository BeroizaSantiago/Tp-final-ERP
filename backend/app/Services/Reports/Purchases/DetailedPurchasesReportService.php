<?php

namespace App\Services\Reports\Purchases;

use App\Models\Purchases\Provider;
use App\Models\Purchases\Purchase;
use Illuminate\Support\Collection;

/** Obtiene una fila por cada producto incluido en comprobantes de compra. */
class DetailedPurchasesReportService
{
    public function generate(array $filters): array
    {
        $purchases = Purchase::query()
            ->with(['provider:id,name', 'items.product:id,name,code,bar_code', 'items.variant:id,bar_code'])
            ->whereDate('issue_date', '>=', $filters['date_from'])->whereDate('issue_date', '<=', $filters['date_to'])
            ->whereRaw("LOWER(COALESCE(status_name,'')) NOT LIKE '%anul%'")
            ->when($filters['provider_ids'] ?? null, fn ($query, $ids) => $query->whereIn('provider_id', $ids))
            ->orderBy('issue_date')->get();

        $rows = $purchases->flatMap(function (Purchase $purchase) {
            $factor = $this->factor($purchase);
            return $purchase->items->map(function ($item) use ($purchase, $factor) {
                $quantity = (float) $item->quantity;
                $discount = $quantity * (float) $item->unit_price * (float) $item->discount_percentage / 100;
                $total = (float) $item->total_amount ?: (float) $item->subtotal_amount + (float) $item->tax_amount;
                return [
                    'type' => $purchase->receipt_type_name ?: 'Compra', 'number' => $purchase->full_number ?: 'Compra #'.$purchase->id,
                    'product' => $item->product_name ?: $item->product?->name ?: 'Sin producto',
                    'provider' => $purchase->provider?->name ?: $purchase->provider_name ?: 'Sin proveedor', 'branch' => 'Sin sucursal',
                    'code' => $item->product_code ?: $item->product?->code ?: '—',
                    'barcode' => $item->variant?->bar_code ?: $item->product?->bar_code ?: '—',
                    'quantity' => round($factor * $quantity, 4), 'unit_price' => round((float) $item->unit_price, 2),
                    'discount' => round($factor * $discount, 2), 'tax_percentage' => (float) $item->tax_percentage,
                    'iva' => round($factor * (float) $item->tax_amount, 2), 'total' => round($factor * $total, 2),
                ];
            });
        })->when($filters['branches'] ?? null, fn (Collection $rows, $branches) => $rows->whereIn('branch', $branches))->values();

        return ['filters' => $filters, 'rows' => $rows->all(), 'summary' => [
            'quantity' => round((float) $rows->sum('quantity'), 4), 'discount' => round((float) $rows->sum('discount'), 2),
            'iva' => round((float) $rows->sum('iva'), 2), 'total' => round((float) $rows->sum('total'), 2),
        ]];
    }

    public function options(): array { return ['branches' => collect(['Sin sucursal']), 'providers' => Provider::query()->orderBy('name')->get(['id','name'])]; }
    public function exportRows(array $report): Collection { return collect($report['rows'])->map(fn ($r) => [$r['type'],$r['number'],$r['product'],$r['provider'],$r['code'],$r['barcode'],$r['quantity'],$r['unit_price'],$r['discount'],$r['tax_percentage'],$r['iva'],$r['total']]); }
    private function factor(Purchase $purchase): int { $name=mb_strtolower((string)$purchase->receipt_type_name); return $purchase->receipt_types_prefix==='NC'||str_contains($name,'crédito')||str_contains($name,'credito') ? -1 : 1; }
}
