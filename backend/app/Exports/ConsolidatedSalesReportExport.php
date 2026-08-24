<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/** Exporta todas las secciones del Reporte de Ventas Consolidado. */
class ConsolidatedSalesReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows) {}

    public function headings(): array
    {
        return ['Sección', 'Concepto', 'Operaciones', 'Unidades', 'Importe', 'Promedio', 'Participación %'];
    }

    public function collection(): Collection
    {
        return $this->rows->map(fn (array $row) => [
            $row['section'], $row['concept'], $row['operations'], $row['units'],
            $row['amount'], $row['average'], $row['percentage'],
        ]);
    }
}