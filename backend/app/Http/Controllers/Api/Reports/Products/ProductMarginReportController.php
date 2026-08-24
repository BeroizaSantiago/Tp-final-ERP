<?php

namespace App\Http\Controllers\Api\Reports\Products;

use App\Exports\ProductMarginReportExport;
use App\Http\Controllers\Controller;
use App\Services\Reports\Products\ProductMarginReportService;
use App\Services\Reports\ReportExportService;
use Illuminate\Http\Request;

/** Intermediario para los márgenes por producto, categoría y marca. */
class ProductMarginReportController extends Controller
{
    public function __construct(private readonly ProductMarginReportService $service, private readonly ReportExportService $exports) {}
    public function index(Request $r, string $grouping)
    {
        return response()->json($this->service->generate($grouping, $this->filters($r, $grouping)));
    }
    public function options()
    {
        return response()->json($this->service->options());
    }
    public function pdf(Request $r, string $grouping)
    {
        $report = $this->service->generate($grouping, $this->filters($r, $grouping));
        return $this->exports->pdf('reports.products.margins.pdf', compact('report'), 'margen-' . $grouping . '-' . now()->format('Ymd-His') . '.pdf');
    }
    public function export(Request $r, string $grouping)
    {
        $f = $this->filters($r, $grouping, true);
        $format = $f['format'];
        unset($f['format']);
        $report = $this->service->generate($grouping, $f);
        return $this->exports->spreadsheet(new ProductMarginReportExport($this->service->exportRows($report), $grouping), 'margen-' . $grouping . '-' . now()->format('Ymd-His') . '.' . $format, $format);
    }
    private function filters(Request $r, string $g, bool $export = false): array
    {
        abort_unless(in_array($g, ['product', 'category', 'brand']), 404);
        $rules = ['date_from' => ['required', 'date'], 'date_to' => ['required', 'date', 'after_or_equal:date_from'], 'branch' => ['nullable', 'string', 'max:255'], $g . '_id' => ['nullable', 'integer', 'exists:' . match ($g) {
            'product' => 'products',
            'category' => 'product_categories',
            'brand' => 'brands'
        } . ',id']];
        if ($export) $rules['format'] = ['required', 'in:xlsx,csv'];
        return $r->validate($rules);
    }
}
