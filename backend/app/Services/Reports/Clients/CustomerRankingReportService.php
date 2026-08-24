<?php

namespace App\Services\Reports\Clients;

use App\Services\Reports\Sales\DetailedSalesReportService;
use Illuminate\Support\Collection;

/** Ordena clientes por operaciones o ventas netas. */
class CustomerRankingReportService
{
    public function __construct(private readonly DetailedSalesReportService $details) {}
    public function generate(array $f): array
    {
        $d = $this->details->generate(['date_from' => $f['date_from'], 'date_to' => $f['date_to'], 'with_variant' => false]);
        $rows = collect($d['rows'])->groupBy(fn($r) => (string)($r['client_id'] ?: $r['client']))->map(function (Collection $items) {
            $operations = $items->unique('invoice_id')->sum(fn($r) => $r['receipt_type'] === 'Nota de Crédito' ? -1 : 1);
            return ['client' => $items->first()['client'], 'total_sales' => round((float)$items->sum('total'), 2), 'operations' => $operations, 'profit' => round((float)$items->sum('profit'), 2)];
        })->sortByDesc($f['order_by'] === 'quantity' ? 'operations' : 'total_sales')->take((int)$f['top'])->values();
        return ['filters' => $f, 'rows' => $rows->all(), 'summary' => ['total_sales' => round((float)$rows->sum('total_sales'), 2), 'operations' => $rows->sum('operations'), 'profit' => round((float)$rows->sum('profit'), 2)]];
    }
    public function exportRows(array $r): Collection
    {
        return collect($r['rows'])->map(fn($x) => [$x['client'], $x['total_sales'], $x['operations'], $x['profit']]);
    }
}
