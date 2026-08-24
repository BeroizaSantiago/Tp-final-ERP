<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerRankingReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $r) {}
    public function headings(): array
    {
        return ['Cliente', 'Total de Ventas', 'Cantidad de Operaciones', 'Ganancia'];
    }
    public function collection(): Collection
    {
        return $this->r;
    }
}
