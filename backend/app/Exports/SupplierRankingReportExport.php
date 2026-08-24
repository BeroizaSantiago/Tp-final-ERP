<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SupplierRankingReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows) {}

    public function headings(): array
    {
        return ['Proveedor', 'Porcentaje', 'Cantidad de Operaciones', 'Total Comprado', 'Fecha del Último Pago', 'Monto del Último Pago'];
    }

    public function collection(): Collection
    {
        return $this->rows;
    }
}
