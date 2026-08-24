<?php

namespace App\Http\Controllers\Api\Reports\Sales;

use App\Exports\SalesListReportExport;
use App\Http\Controllers\Controller;
use App\Services\Reports\ReportExportService;
use App\Services\Reports\Sales\SalesListReportService;
use Illuminate\Http\Request;

/** Intermediario HTTP del Listado de Ventas. */
class SalesListReportController extends Controller
{
    public function __construct(private readonly SalesListReportService $service, private readonly ReportExportService $exports) {}

    public function index(Request $request) { return response()->json($this->service->generate($this->filters($request))); }
    public function options() { return response()->json($this->service->options()); }

    public function pdf(Request $request)
    {
        $report = $this->service->generate($this->filters($request));
        return $this->exports->pdf('reports.sales.list.pdf', compact('report'), 'listado-ventas-'.now()->format('Ymd-His').'.pdf');
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request, true);
        $format = $filters['format']; unset($filters['format']);
        $report = $this->service->generate($filters);
        return $this->exports->spreadsheet(new SalesListReportExport($this->service->exportRows($report)), 'listado-ventas-'.now()->format('Ymd-His').'.'.$format, $format);
    }

    private function filters(Request $request, bool $export = false): array
    {
        $rules = [
            'date_from' => ['required', 'date'], 'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'operation_types' => ['nullable', 'array'], 'operation_types.*' => ['string', 'max:80'],
            'branch' => ['nullable', 'string', 'max:255'], 'channel' => ['nullable', 'string', 'max:100'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'], 'province' => ['nullable', 'string', 'max:100'],
            'point_of_sale' => ['nullable', 'string', 'max:100'], 'seller_id' => ['nullable', 'integer', 'exists:users,id'],
            'price_type' => ['nullable', 'string', 'max:100'],
        ];
        if ($export) $rules['format'] = ['required', 'in:xlsx,csv'];
        return $request->validate($rules);
    }
}
