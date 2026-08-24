<?php

namespace App\Http\Controllers\Api\Reports\Products;

use App\Exports\ProductSalesRankingReportExport;
use App\Http\Controllers\Controller;
use App\Services\Reports\Products\ProductSalesRankingReportService;
use App\Services\Reports\ReportExportService;
use Illuminate\Http\Request;

/** Intermediario HTTP del Ranking de Productos Vendidos. */
class ProductSalesRankingReportController extends Controller
{
    public function __construct(private readonly ProductSalesRankingReportService $service, private readonly ReportExportService $exports) {}
    public function index(Request $r)
    {
        return response()->json($this->service->generate($this->filters($r)));
    }
    public function options()
    {
        return response()->json($this->service->options());
    }
    public function pdf(Request $r)
    {
        $report = $this->service->generate($this->filters($r));
        return $this->exports->pdf('reports.products.ranking.pdf', compact('report'), 'ranking-productos-' . now()->format('Ymd-His') . '.pdf');
    }
    public function export(Request $r)
    {
        $f = $this->filters($r, true);
        $format = $f['format'];
        unset($f['format']);
        $report = $this->service->generate($f);
        return $this->exports->spreadsheet(new ProductSalesRankingReportExport($this->service->exportRows($report)), 'ranking-productos-' . now()->format('Ymd-His') . '.' . $format, $format);
    }
    private function filters(Request $r, bool $export = false): array
    {
        $rules = ['date_from' => ['required', 'date'], 'date_to' => ['required', 'date', 'after_or_equal:date_from'], 'list_by' => ['required', 'in:product,category,brand,model,provider'], 'top' => ['required', 'integer', 'min:1', 'max:500'], 'category_id' => ['nullable', 'integer', 'exists:product_categories,id'], 'order_by' => ['required', 'in:units,total_sale,operations,profit'], 'size' => ['nullable', 'string', 'max:100'], 'color' => ['nullable', 'string', 'max:100'], 'provider_id' => ['nullable', 'integer', 'exists:providers,id'], 'brand_id' => ['nullable', 'integer', 'exists:brands,id'], 'model_id' => ['nullable', 'integer', 'exists:product_models,id'], 'with_variant' => ['nullable', 'boolean']];
        if ($export) $rules['format'] = ['required', 'in:xlsx,csv'];
        $d = $r->validate($rules);
        $d['with_variant'] = (bool)($d['with_variant'] ?? false);
        return $d;
    }
}
