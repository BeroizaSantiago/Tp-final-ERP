<?php

namespace App\Services\Reports\Purchases;

use App\Models\Purchases\Provider;
use App\Models\Purchases\Purchase;
use Illuminate\Support\Collection;

/** Normaliza los comprobantes del Listado de Compras. */
class PurchasesReportService
{
    public function generate(array $f): array
    {
        $rows = $this->query($f)->get()->map(function (Purchase $p) {
            $factor = $this->factor($p);
            return ['type' => $p->receipt_type_name ?: 'Compra', 'number' => $p->full_number ?: 'Compra #' . $p->id, 'date' => optional($p->issue_date)->format('d/m/Y'), 'provider' => $p->provider?->name ?: $p->provider_name ?: 'Sin proveedor', 'province' => $p->provider?->province_name ?: 'Sin informar', 'branch' => 'Sin sucursal', 'net' => round($factor * (float)$p->taxed_amount, 2), 'non_taxed' => round($factor * (float)$p->non_taxed_amount, 2), 'exempt' => round($factor * (float)$p->exempt_amount, 2), 'surcharge' => round($factor * (float)$p->surcharge_amount, 2), 'discount' => round($factor * (float)$p->discount_amount, 2), 'iva' => round($factor * (float)$p->tax_amount, 2), 'total' => round($factor * (float)$p->total_amount, 2)];
        })->when($f['branches'] ?? null, fn(Collection $r, $v) => $r->whereIn('branch', $v))->values();
        return ['filters' => $f, 'rows' => $rows->all(), 'summary' => ['net' => round((float)$rows->sum('net'), 2), 'non_taxed' => round((float)$rows->sum('non_taxed'), 2), 'exempt' => round((float)$rows->sum('exempt'), 2), 'surcharge' => round((float)$rows->sum('surcharge'), 2), 'discount' => round((float)$rows->sum('discount'), 2), 'iva' => round((float)$rows->sum('iva'), 2), 'total' => round((float)$rows->sum('total'), 2)]];
    }
    public function options(): array
    {
        return ['branches' => collect(['Sin sucursal']), 'types' => Purchase::query()->select(['receipt_types_prefix', 'receipt_type_name'])->distinct()->get()->map(fn($p) => ['value' => $p->receipt_types_prefix ?: $p->receipt_type_name, 'label' => $p->receipt_type_name ?: $p->receipt_types_prefix])->values(), 'providers' => Provider::query()->orderBy('name')->get(['id', 'name']), 'provinces' => Provider::query()->whereNotNull('province_name')->where('province_name', '!=', '')->distinct()->orderBy('province_name')->pluck('province_name')];
    }
    public function exportRows(array $r): Collection
    {
        return collect($r['rows'])->map(fn($x) => [$x['type'], $x['number'], $x['date'], $x['provider'], $x['province'], $x['net'], $x['non_taxed'], $x['exempt'], $x['surcharge'], $x['discount'], $x['iva'], $x['total']]);
    }
    private function query(array $f)
    {
        return Purchase::query()->with('provider:id,name,province_name')->whereDate('issue_date', '>=', $f['date_from'])->whereDate('issue_date', '<=', $f['date_to'])->whereRaw("LOWER(COALESCE(status_name,'')) NOT LIKE '%anul%'")->when($f['operation_types'] ?? null, fn($q, $v) => $q->where(fn($x) => $x->whereIn('receipt_types_prefix', $v)->orWhereIn('receipt_type_name', $v)))->when($f['provider_ids'] ?? null, fn($q, $v) => $q->whereIn('provider_id', $v))->when($f['provinces'] ?? null, fn($q, $v) => $q->whereHas('provider', fn($p) => $p->whereIn('province_name', $v)))->orderBy('issue_date')->orderBy('id');
    }
    private function factor(Purchase $p): int
    {
        $n = mb_strtolower((string)$p->receipt_type_name);
        return $p->receipt_types_prefix === 'NC' || str_contains($n, 'crédito') || str_contains($n, 'credito') ? -1 : 1;
    }
}
