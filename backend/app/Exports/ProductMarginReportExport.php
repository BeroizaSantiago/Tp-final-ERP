<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/** Exporta cualquiera de los tres reportes de margen. */
class ProductMarginReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows, private readonly string $grouping) {}

    public function headings(): array
    {
        return [
            'Código',
            match ($this->grouping) {
                'category' => 'Categoría',
                'brand' => 'Marca',
                default => 'Producto',
            },
            'Total Venta', 'Ganancia', 'Margen Promedio', 'Rentabilidad Promedio', 'Porcentaje',
        ];
    }

    public function collection(): Collection { return $this->rows; }
}
