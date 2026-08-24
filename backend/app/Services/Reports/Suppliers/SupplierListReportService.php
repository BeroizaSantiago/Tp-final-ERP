<?php

namespace App\Services\Reports\Suppliers;

use App\Models\Purchases\Provider;
use Illuminate\Support\Collection;

/** Construye el listado general desde el maestro de proveedores. */
class SupplierListReportService
{
    public function generate(array $filters = []): array
    {
        $rows = Provider::query()->orderBy('name')->get()->map(fn ($provider) => [
            'provider' => $provider->name,
            'identification' => $provider->identification_number ?: '-',
            'address' => $provider->address ?: '-',
            'payment_condition' => 'Sin informar',
            'phone' => $provider->primary_phone ?: '-',
            'email' => 'Sin informar',
        ]);

        return ['filters' => $filters, 'rows' => $rows->all(), 'summary' => ['records' => $rows->count()]];
    }

    public function exportRows(array $report): Collection
    {
        return collect($report['rows'])->map(fn ($row) => array_values($row));
    }
}
