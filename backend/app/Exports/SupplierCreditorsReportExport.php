<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SupplierCreditorsReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows) {}

    public function headings(): array
    {
        return ['Proveedor', 'Moneda', 'Saldo', 'Deuda Vencida', 'Deuda Futura', 'Fecha del Último Pago', 'Monto del Último Pago', 'E-mail', 'Teléfono'];
    }

    public function collection(): Collection
    {
        return $this->rows;
    }
}
