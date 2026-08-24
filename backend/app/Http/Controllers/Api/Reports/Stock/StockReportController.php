<?php

namespace App\Http\Controllers\Api\Reports\Stock;

use App\Exports\StockBySalesReportExport;
use App\Exports\StockMovementReportExport;
use App\Exports\StockReplenishmentReportExport;
use App\Exports\StockTreeReportExport;
use App\Http\Controllers\Controller;
use App\Services\Reports\ReportExportService;
use App\Services\Reports\Stock\StockBySalesReportService;
use App\Services\Reports\Stock\StockMovementReportService;
use App\Services\Reports\Stock\StockReplenishmentReportService;
use App\Services\Reports\Stock\StockTreeReportService;
use Illuminate\Http\Request;

/** Valida y entrega los reportes del módulo de Stock. */
class StockReportController extends Controller
{
    public function __construct(
        private readonly StockMovementReportService $movements,
        private readonly StockReplenishmentReportService $replenishment,
        private readonly StockBySalesReportService $stockBySales,
        private readonly StockTreeReportService $stockTree,
        private readonly ReportExportService $exports,
    ) {}

    public function movementOptions() { return response()->json($this->movements->options()); }
    public function movements(Request $request) { return response()->json($this->movements->generate($this->movementFilters($request))); }
    public function movementsPdf(Request $request) { $report = $this->movements->generate($this->movementFilters($request)); return $this->exports->pdf('reports.stock.movements.pdf', compact('report'), 'movimientos-stock.pdf'); }
    public function movementsExport(Request $request) { $filters = $this->movementFilters($request, true); $format = $filters['format']; unset($filters['format']); $report = $this->movements->generate($filters); return $this->exports->spreadsheet(new StockMovementReportExport($this->movements->exportRows($report)), 'movimientos-stock.'.$format, $format); }

    public function replenishmentOptions() { return response()->json($this->replenishment->options()); }
    public function replenishment(Request $request) { return response()->json($this->replenishment->generate($this->replenishmentFilters($request))); }
    public function replenishmentPdf(Request $request) { $report = $this->replenishment->generate($this->replenishmentFilters($request)); return $this->exports->pdf('reports.stock.replenishment.pdf', compact('report'), 'reposicion-stock.pdf'); }
    public function replenishmentExport(Request $request) { $filters = $this->replenishmentFilters($request, true); $format = $filters['format']; unset($filters['format']); $report = $this->replenishment->generate($filters); return $this->exports->spreadsheet(new StockReplenishmentReportExport($this->replenishment->exportRows($report), $report['criterion_label']), 'reposicion-stock.'.$format, $format); }

    public function stockBySalesOptions() { return response()->json($this->stockBySales->options()); }
    public function stockBySales(Request $request) { return response()->json($this->stockBySales->generate($this->stockBySalesFilters($request))); }
    public function stockBySalesExport(Request $request) { $filters = $this->stockBySalesFilters($request, true); unset($filters['format'], $filters['page']); $report = $this->stockBySales->generate($filters, false); return $this->exports->spreadsheet(new StockBySalesReportExport($report), 'stock-segun-ventas-'.now()->format('Ymd-His').'.xlsx', 'xlsx'); }

    public function stockOptions() { return response()->json($this->stockTree->options()); }
    public function stock(Request $request) { return response()->json($this->stockTree->generate($this->stockFilters($request))); }
    public function stockExport(Request $request) { $filters = $this->stockFilters($request, true); unset($filters['format'], $filters['page']); $report = $this->stockTree->generate($filters, false); return $this->exports->spreadsheet(new StockTreeReportExport($report), 'stock-valorizado-'.now()->format('Ymd-His').'.xlsx', 'xlsx'); }

    private function movementFilters(Request $request, bool $export = false): array
    {
        $rules = ['date_from'=>['required','date'],'date_to'=>['required','date','after_or_equal:date_from'],'product_ids'=>['nullable','array'],'product_ids.*'=>['integer','exists:products,id'],'name'=>['nullable','string','max:255'],'branches'=>['nullable','array'],'branches.*'=>['string','max:255'],'warehouses'=>['nullable','array'],'warehouses.*'=>['string','max:255'],'modules'=>['nullable','array'],'modules.*'=>['in:sales,stock,purchases'],'operation_types'=>['nullable','array'],'operation_types.*'=>['string','max:100'],'reason_ids'=>['nullable','array'],'reason_ids.*'=>['integer','exists:stock_adjustment_reasons,id']];
        if ($export) $rules['format'] = ['required','in:xlsx,csv'];
        return $request->validate($rules);
    }

    private function replenishmentFilters(Request $request, bool $export = false): array
    {
        $rules = ['criterion'=>['required','in:reposition,minimum'],'branch'=>['required','string','max:255'],'warehouses'=>['nullable','array'],'warehouses.*'=>['string','max:255'],'product_ids'=>['nullable','array'],'product_ids.*'=>['integer','exists:products,id'],'provider_ids'=>['nullable','array'],'provider_ids.*'=>['integer','exists:providers,id'],'category_ids'=>['nullable','array'],'category_ids.*'=>['integer','exists:product_categories,id'],'brand_ids'=>['nullable','array'],'brand_ids.*'=>['integer','exists:brands,id'],'model_ids'=>['nullable','array'],'model_ids.*'=>['integer','exists:product_models,id'],'include_variants'=>['required','boolean']];
        if ($export) $rules['format'] = ['required','in:xlsx,csv'];
        return $request->validate($rules);
    }

    private function stockBySalesFilters(Request $request, bool $export = false): array
    {
        $rules = ['date_from'=>['required','date'],'date_to'=>['required','date','after_or_equal:date_from'],'warehouse'=>['nullable','string','max:255'],'product_ids'=>['nullable','array'],'product_ids.*'=>['integer','exists:products,id'],'name'=>['nullable','string','max:255'],'provider_ids'=>['nullable','array'],'provider_ids.*'=>['integer','exists:providers,id'],'category_ids'=>['nullable','array'],'category_ids.*'=>['integer','exists:product_categories,id'],'brand_ids'=>['nullable','array'],'brand_ids.*'=>['integer','exists:brands,id'],'model_ids'=>['nullable','array'],'model_ids.*'=>['integer','exists:product_models,id'],'page'=>['nullable','integer','min:1'],'per_page'=>['nullable','integer','in:10,25,50,100']];
        if ($export) $rules['format'] = ['required','in:xlsx'];
        return $request->validate($rules);
    }

    private function stockFilters(Request $request, bool $export = false): array
    {
        $rules = ['product'=>['nullable','string','min:3','max:255'],'size_id'=>['nullable','integer','exists:sizes,id'],'size_text'=>['nullable','string','max:100'],'color_id'=>['nullable','integer','exists:colors,id'],'warehouse'=>['nullable','string','max:255'],'currency'=>['nullable','string','max:20'],'brand_id'=>['nullable','integer','exists:brands,id'],'model_id'=>['nullable','integer','exists:product_models,id'],'category_id'=>['nullable','integer','exists:product_categories,id'],'provider'=>['nullable','string','max:255'],'active'=>['required','in:active,inactive,all'],'last_movement_from'=>['nullable','date'],'reference_code'=>['nullable','string','max:255'],'physical_warehouses'=>['required','boolean'],'variants_with_stock'=>['required','boolean'],'origin_currency'=>['required','boolean'],'by_dispatch'=>['required','boolean'],'show_thresholds'=>['required','boolean'],'page'=>['nullable','integer','min:1'],'per_page'=>['nullable','integer','in:10,25,50,100']];
        if ($export) $rules['format'] = ['required','in:xlsx'];
        return $request->validate($rules, [
            'product.min' => 'Ingresá al menos 3 caracteres para buscar el producto.',
            'size_id.exists' => 'El talle seleccionado no existe en la base de datos.',
            'color_id.exists' => 'El color seleccionado no existe en la base de datos.',
            'brand_id.exists' => 'La marca seleccionada no existe en la base de datos.',
            'model_id.exists' => 'El modelo seleccionado no existe en la base de datos.',
            'category_id.exists' => 'La categoría seleccionada no existe en la base de datos.',
        ]);
    }
}
