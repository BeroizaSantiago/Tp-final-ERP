<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/** Exporta el cruce de existencias y ventas netas por producto y variante. */
class StockBySalesReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly array $report) {}
    public function headings(): array { return ['Nivel', 'Código', 'Producto', 'Variante', 'Color', 'Talle', 'Sucursal', 'Depósito', 'Stock actual', 'Unidades vendidas netas']; }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->report['rows'] as $product) {
            $rows->push(['Producto', $product['code'], $product['product'], '', '', '', '', '', $product['stock'], $product['sold']]);
            foreach ($product['variants'] as $variant) $rows->push(['Variante', $product['code'], $product['product'], $variant['variant'], $variant['color'], $variant['size'], $variant['branch'], $variant['warehouse'], $variant['stock'], $variant['sold']]);
        }
        return $rows;
    }
}
