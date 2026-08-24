<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PurchasesReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows) {}
    public function headings(): array
    {
        return ['Tipo', 'Número', 'Fecha', 'Proveedor', 'Provincia', 'Neto', 'No Gravado', 'Exento', 'Recargo', 'Descuento', 'IVA', 'Total'];
    }
    public function collection(): Collection
    {
        return $this->rows;
    }
}
