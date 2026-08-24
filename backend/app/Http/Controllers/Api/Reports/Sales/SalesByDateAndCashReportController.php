<?php
namespace App\Http\Controllers\Api\Reports\Sales;
use App\Exports\SalesByDateAndCashReportExport;
use App\Http\Controllers\Controller;
use App\Services\Reports\ReportExportService;
use App\Services\Reports\Sales\SalesByDateAndCashReportService;
use Illuminate\Http\Request;
/** Intermediario HTTP del Reporte de Ventas por Fechas y Cajas. */
class SalesByDateAndCashReportController extends Controller
{
    public function __construct(private readonly SalesByDateAndCashReportService $service,private readonly ReportExportService $exports){}
    public function index(Request $r){return response()->json($this->service->generate($this->filters($r)));}
    public function options(){return response()->json($this->service->options());}
    public function pdf(Request $r){$report=$this->service->generate($this->filters($r));return$this->exports->pdf('reports.sales.by-date-cash.pdf',compact('report'),'ventas-fechas-cajas-'.now()->format('Ymd-His').'.pdf');}
    public function export(Request $r){$filters=$this->filters($r,true);$format=$filters['format'];unset($filters['format']);$report=$this->service->generate($filters);return$this->exports->spreadsheet(new SalesByDateAndCashReportExport($this->service->exportRows($report)),'ventas-fechas-cajas-'.now()->format('Ymd-His').'.'.$format,$format);}
    private function filters(Request $r,bool $export=false):array{$rules=['date_from'=>['required','date'],'date_to'=>['required','date','after_or_equal:date_from'],'cash_box_ids'=>['nullable','array'],'cash_box_ids.*'=>['integer','exists:cash_boxes,id']];if($export)$rules['format']=['required','in:xlsx,csv'];return$r->validate($rules);}
}
