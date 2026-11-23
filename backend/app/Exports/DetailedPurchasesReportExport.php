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
        return ['Tipo', 'Fecha', 'Número', 'Libro', 'Autor', 'Editorial', 'Género', 'Colección', 'Proveedor', 'Código', 'ISBN', 'Cantidad', 'Precio unitario', 'Descuento', 'Porcentaje de IVA', 'IVA', 'Total'];
    }

    public function collection(): Collection
    {
        return $this->rows;
    }
}
