<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerListReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $r) {}
    public function headings(): array
    {
        return ['Cliente', 'Identificación', 'Dirección', 'Condición de Pago', 'Teléfono', 'E-mail', 'Condición frente al IVA', 'Fecha de Nacimiento', 'Referido'];
    }
    public function collection(): Collection
    {
        return $this->r;
    }
}
