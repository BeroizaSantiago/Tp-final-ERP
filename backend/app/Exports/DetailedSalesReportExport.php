<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/** Exporta las filas por producto del Listado con Detalle de Ventas. */
class DetailedSalesReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows, private readonly bool $withVariant) {}
    public function headings(): array
    {
        $columns = ['Tipo de comprobante','Fecha','Número','Canal','Sucursal','Cliente','Producto','Marca','Modelo','Categoría','Lista de precios','Código interno','Código de barras','Código de referencia'];
        if ($this->withVariant) array_push($columns, 'Talle', 'Color', 'Variante');
        return array_merge($columns, ['Cantidad','Costo','Precio unitario','Descuento','% descuento','Alícuota IVA','Importe IVA','Ganancia','Total del ítem','Método de pago','Financiación']);
    }
    public function collection(): Collection { return $this->rows; }
}
