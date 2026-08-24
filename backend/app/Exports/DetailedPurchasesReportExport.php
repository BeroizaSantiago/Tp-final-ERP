<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DetailedPurchasesReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows) {}
    public function headings(): array
    {
        return ['Tipo', 'Número', 'Producto', 'Proveedor', 'Código', 'Código de Barras', 'Cantidad', 'Precio Unitario', 'Descuento', 'Porcentaje de IVA', 'IVA', 'Total'];
    }
    public function collection(): Collection
    {
        return $this->rows;
    }
}
