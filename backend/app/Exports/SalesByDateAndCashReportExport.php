<?php
namespace App\Exports;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
/** Exporta la misma jerarquía del Reporte de Ventas por Fechas y Cajas. */
class SalesByDateAndCashReportExport implements FromCollection,WithHeadings
{
    public function __construct(private readonly Collection $rows){}
    public function headings():array{return['Fecha','Caja','Cajero','Método de Pago','Cantidad de Operaciones','Importe Total','Nivel'];}
    public function collection():Collection{return$this->rows;}
}
