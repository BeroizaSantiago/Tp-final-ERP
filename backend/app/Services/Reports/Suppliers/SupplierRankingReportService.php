<?php

namespace App\Services\Reports\Suppliers;

use App\Models\Purchases\Purchase;
use Illuminate\Support\Collection;

/** Calcula compras netas y participación por proveedor sin persistir resultados. */
class SupplierRankingReportService
{
    public function generate(array $filters): array
    {
        $purchases = Purchase::query()
            ->with([
                'provider:id,name',
                'provider.paymentOrders' => fn ($query) => $query->where('status', '!=', 'cancelled')->latest('issue_date'),
            ])
            ->whereDate('issue_date', '>=', $filters['date_from'])
            ->whereDate('issue_date', '<=', $filters['date_to'])
            ->whereRaw("LOWER(COALESCE(status_name,'')) NOT LIKE '%anul%'")
            ->get();

        $rows = $purchases->groupBy(fn ($purchase) => (string) ($purchase->provider_id ?: $purchase->provider_name))
            ->map(function (Collection $items) {
                $first = $items->first();
                $total = $items->sum(fn ($purchase) => $this->isCreditNote($purchase)
                    ? -abs((float) $purchase->total_amount)
                    : (float) $purchase->total_amount);
                // Una nota de crédito resta importes, pero continúa siendo un comprobante registrado.
                $operations = $items->count();
                $lastPayment = $first->provider?->paymentOrders?->first();

                return [
                    'provider' => $first->provider?->name ?: $first->provider_name ?: 'Sin proveedor',
                    'percentage' => 0.0,
                    'operations' => $operations,
                    'total' => round((float) $total, 2),
                    'last_payment_date' => $lastPayment?->issue_date?->format('d/m/Y') ?: '-',
                    'last_payment_amount' => (float) ($lastPayment?->total_amount ?? 0),
                ];
            })->values();

        // La participación se calcula sobre todo el período antes de limitar el ranking.
        $periodTotal = round((float) $rows->sum('total'), 2);
        $rows = $rows->map(function (array $row) use ($periodTotal) {
            $row['percentage'] = $periodTotal != 0 ? round($row['total'] * 100 / $periodTotal, 2) : 0;
            return $row;
        })->sortByDesc('total')->take((int) $filters['top'])->values();

        return [
            'filters' => $filters,
            'rows' => $rows->all(),
            'summary' => [
                'total' => round((float) $rows->sum('total'), 2),
                'operations' => $rows->sum('operations'),
            ],
        ];
    }

    public function exportRows(array $report): Collection
    {
        return collect($report['rows'])->map(fn ($row) => array_values($row));
    }

    private function isCreditNote($purchase): bool
    {
        $name = mb_strtolower((string) $purchase->receipt_type_name);
        return $purchase->receipt_types_prefix === 'NC'
            || str_contains($name, 'crédito')
            || str_contains($name, 'credito');
    }
}
