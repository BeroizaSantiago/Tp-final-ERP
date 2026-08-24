<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockMovementReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $r) {}
    public function headings(): array
    {
        return ['Fecha', 'Tipo de Operación', 'Motivo', 'Número de Comprobante', 'Código de Producto', 'Código de Barras', 'Producto', 'Cantidad', 'Stock', 'Usuario'];
    }
    public function collection(): Collection
    {
        return $this->r;
    }
}
