<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/** Exporta el Listado de Comisiones calculado por el servicio de reportes. */
class SalesCommissionReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows) {}
    public function headings(): array
    {
        return ['Vendedor','Fecha del Comprobante','Tipo de Comprobante','Número','Cliente','Punto de Venta','Importe de Venta','Importe Cobrado','Comisión por Cobro','Comisión por Venta','Comisión por Ganancia','Total de Comisión'];
    }
    public function collection(): Collection { return $this->rows; }
}
