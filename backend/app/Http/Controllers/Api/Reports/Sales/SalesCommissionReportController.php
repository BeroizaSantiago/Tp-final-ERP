<?php

namespace App\Http\Controllers\Api\Reports\Sales;

use App\Exports\SalesCommissionReportExport;
use App\Http\Controllers\Controller;
use App\Services\Reports\ReportExportService;
use App\Services\Reports\Sales\SalesCommissionReportService;
use Illuminate\Http\Request;

/** Controlador del Listado de Comisiones de vendedores. */
class SalesCommissionReportController extends Controller
{
    public function __construct(private readonly SalesCommissionReportService $service, private readonly ReportExportService $exports) {}
    public function index(Request $request) { return response()->json($this->service->generate($this->filters($request))); }
    public function options() { return response()->json($this->service->options()); }
    public function pdf(Request $request)
    {
        $report = $this->service->generate($this->filters($request));
        return $this->exports->pdf('reports.sales.commissions.pdf', compact('report'), 'listado-comisiones-'.now()->format('Ymd-His').'.pdf');
    }
    public function export(Request $request)
    {
        $filters = $this->filters($request, true); $format = $filters['format']; unset($filters['format']);
        $report = $this->service->generate($filters);
        return $this->exports->spreadsheet(new SalesCommissionReportExport($this->service->exportRows($report)), 'listado-comisiones-'.now()->format('Ymd-His').'.'.$format, $format);
    }
    private function filters(Request $request, bool $export = false): array
    {
        $rules=['date_from'=>['required','date'],'date_to'=>['required','date','after_or_equal:date_from'],'criterion'=>['required','in:sale,collection,profit'],'seller_id'=>['nullable','integer','exists:users,id'],'client_id'=>['nullable','integer','exists:clients,id'],'point_of_sale'=>['nullable','string','max:255'],'gross_sale_commission'=>['nullable','boolean']];
        if($export)$rules['format']=['required','in:xlsx,csv'];
        $data=$request->validate($rules); $data['gross_sale_commission']=(bool)($data['gross_sale_commission']??false); return $data;
    }
}
