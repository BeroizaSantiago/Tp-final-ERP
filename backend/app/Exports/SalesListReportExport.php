<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/** Exporta el detalle del Listado de Ventas preparado por el servicio. */
class SalesListReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows) {}

    public function headings(): array
    {
        return ['Fecha', 'Tipo de comprobante', 'Número', 'Canal', 'Sucursal', 'Documento asociado',
            'Cliente', 'Documento del cliente', 'Provincia', 'Vendedor', 'Lista de precios', 'Neto',
            'No gravado', 'Exento', 'Recargos', 'Descuentos', '% descuento', 'Redondeo',
            'Costo de envío', 'IVA', 'Ganancia', 'Unidades', 'Total', 'Método de pago', 'Financiación'];
    }

    public function collection(): Collection { return $this->rows; }
}
