<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FinancialResultReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows) {}
    public function headings(): array
    {
        return ['Período', 'Cobros', 'Pagos', 'Resultado'];
    }
    public function collection(): Collection
    {
        return $this->rows;
    }
}
