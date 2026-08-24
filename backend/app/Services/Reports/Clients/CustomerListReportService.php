<?php

namespace App\Services\Reports\Clients;

use App\Models\Clients\Client;
use Illuminate\Support\Collection;

/** Lista la información maestra de clientes. */
class CustomerListReportService
{
    public function generate(array $f): array
    {
        $rows = Client::query()->when($f['sellers'] ?? null, fn($q, $v) => $q->whereIn('seller', $v))->orderBy('name')->get()->map(fn($c) => ['client' => $c->name, 'identification' => $c->document_number ?: '—', 'address' => trim(implode(' ', array_filter([$c->address, $c->address_number]))) ?: '—', 'payment_condition' => $c->payment_condition_detail ?: $c->payment_condition ?: 'Sin informar', 'phone' => $c->first_phone ?: $c->second_phone ?: '—', 'email' => $c->email ?: '—', 'vat_condition' => $c->vat_classification ?: 'Sin informar', 'birth_date' => optional($c->birth_date)->format('d/m/Y') ?: '—', 'referred' => $c->referred ?: '—']);
        return ['filters' => $f, 'rows' => $rows->all(), 'summary' => ['records' => $rows->count()]];
    }
    public function options(): array
    {
        return ['sellers' => Client::query()->whereNotNull('seller')->where('seller', '!=', '')->distinct()->orderBy('seller')->pluck('seller')];
    }
    public function exportRows(array $r): Collection
    {
        return collect($r['rows'])->map(fn($x) => array_values($x));
    }
}
