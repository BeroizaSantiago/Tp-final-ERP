<?php

namespace App\Http\Controllers\Api\Reports\Sales;

use App\Exports\DetailedSalesReportExport;
use App\Http\Controllers\Controller;
use App\Services\Reports\ReportExportService;
use App\Services\Reports\Sales\DetailedSalesReportService;
use Illuminate\Http\Request;

/** Intermediario HTTP del Listado con Detalle de Ventas. */
class DetailedSalesReportController extends Controller
{
    public function __construct(private readonly DetailedSalesReportService $service, private readonly ReportExportService $exports) {}
    public function index(Request $request) { return response()->json($this->service->generate($this->filters($request))); }
    public function options() { return response()->json($this->service->options()); }
    public function pdf(Request $request)
    {
        $report = $this->service->generate($this->filters($request));
        return $this->exports->pdf('reports.sales.detailed.pdf', compact('report'), 'detalle-ventas-'.now()->format('Ymd-His').'.pdf', 'landscape', 'A3');
    }
    public function export(Request $request)
    {
        $filters = $this->filters($request, true); $format = $filters['format']; unset($filters['format']);
        $report = $this->service->generate($filters);
        return $this->exports->spreadsheet(new DetailedSalesReportExport($this->service->exportRows($report), $report['filters']['with_variant']), 'detalle-ventas-'.now()->format('Ymd-His').'.'.$format, $format);
    }
    private function filters(Request $request, bool $export = false): array
    {
        $rules = ['date_from'=>['required','date'],'date_to'=>['required','date','after_or_equal:date_from'],
            'branch'=>['nullable','string','max:255'],'point_of_sale'=>['nullable','string','max:100'],
            'client_id'=>['nullable','integer','exists:clients,id'],'channel'=>['nullable','string','max:100'],
            'product_id'=>['nullable','integer','exists:products,id'],'provider_id'=>['nullable','integer','exists:providers,id'],
            'brand_id'=>['nullable','integer','exists:brands,id'],'model_id'=>['nullable','integer','exists:product_models,id'],
            'category_id'=>['nullable','integer','exists:product_categories,id'],'seller_id'=>['nullable','integer','exists:users,id'],
            'price_type'=>['nullable','string','max:100'],'with_variant'=>['nullable','boolean']];
        if ($export) $rules['format']=['required','in:xlsx,csv'];
        $data=$request->validate($rules); $data['with_variant']=(bool)($data['with_variant']??false); return $data;
    }
}
