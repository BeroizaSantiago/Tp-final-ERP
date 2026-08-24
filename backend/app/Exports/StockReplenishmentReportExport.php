<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockReplenishmentReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $r, private readonly string $criterion) {}
    public function headings(): array
    {
        return ['Código', 'Código de Barras', 'Producto', 'Talle', 'Color', $this->criterion, 'Stock Actual', 'Sucursal', 'Depósito', 'Proveedor', 'Categoría', 'Marca', 'Modelo', 'Cantidad Sugerida'];
    }
    public function collection(): Collection
    {
        return $this->r;
    }
}
