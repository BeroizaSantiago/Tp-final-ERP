<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/** Exporta el árbol de stock respetando sus columnas dinámicas. */
class StockTreeReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly array $report) {}

    public function headings(): array
    {
        $headings = ['Nivel', 'Código', 'Producto / Variante'];
        foreach ($this->report['columns'] as $column) {
            $headings[] = $column['label'].' - Unidades';
            $headings[] = $column['label'].' - Valorizado';
        }
        return array_merge($headings, ['Gran Total - Unidades', 'Gran Total - Valorizado']);
    }

    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->report['rows'] as $product) {
            $rows->push($this->row('Producto', $product['code'], $product['name'], $product['cells']));
            if (! ($this->report['context']['show_variants'] ?? false)) continue;
            foreach ($product['colors'] as $color) {
                $rows->push($this->row('Color', '', $color['label'], $color['cells']));
                foreach ($color['sizes'] as $size) $rows->push($this->row('Talle', '', $size['label'], $size['cells']));
            }
        }
        return $rows;
    }

    private function row(string $level, string $code, string $label, array $cells): array
    {
        $row = [$level, $code, $label];
        foreach ($this->report['columns'] as $column) {
            $row[] = $cells[$column['key']]['units'] ?? 0;
            $row[] = $cells[$column['key']]['valued'] ?? 0;
        }
        $row[] = $cells['grand_total']['units'] ?? 0;
        $row[] = $cells['grand_total']['valued'] ?? 0;
        return $row;
    }
}
