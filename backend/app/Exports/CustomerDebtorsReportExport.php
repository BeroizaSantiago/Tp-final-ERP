<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerDebtorsReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $r) {}
    public function headings(): array
    {
        return ['Cliente', 'Moneda', 'Saldo', 'Deuda Vencida', 'Deuda Futura', 'Fecha del Último Cobro', 'Monto del Último Cobro', 'Vendedor', 'E-mail', 'Teléfono'];
    }
    public function collection(): Collection
    {
        return $this->r;
    }
}
