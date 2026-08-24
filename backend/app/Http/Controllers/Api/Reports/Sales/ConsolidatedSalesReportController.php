<?php

namespace App\Http\Controllers\Api\Reports\Sales;

use App\Exports\ConsolidatedSalesReportExport;
use App\Http\Controllers\Controller;
use App\Services\Reports\ReportExportService;
use App\Services\Reports\Sales\ConsolidatedSalesReportService;
use Illuminate\Http\Request;

/**
 * Controlador del Reporte de Ventas Consolidado.
 *
 * Expone consulta y exportaciones delegando todos los cálculos al servicio del reporte.
 */
class ConsolidatedSalesReportController extends Controller
{
    public function __construct(
        private readonly ConsolidatedSalesReportService $reportService,
        private readonly ReportExportService $exportService,
    ) {}

    public function index(Request $request)
    {
        return response()->json($this->reportService->generate($this->filters($request)));
    }

    public function options()
    {
        return response()->json(['branches' => $this->reportService->branches()]);
    }

    public function pdf(Request $request)
    {
        $report = $this->reportService->generate($this->filters($request));
        return $this->exportService->pdf(
            'reports.sales.consolidated.pdf',
            compact('report'),
            'reporte-ventas-consolidado-' . now()->format('Ymd-His') . '.pdf'
        );
    }

    public function export(Request $request)
    {
        $data = $this->filters($request, true);
        $format = $data['format'];
        unset($data['format']);
        $report = $this->reportService->generate($data);

        return $this->exportService->spreadsheet(
            new ConsolidatedSalesReportExport($this->reportService->exportRows($report)),
            'reporte-ventas-consolidado-' . now()->format('Ymd-His') . '.' . $format,
            $format
        );
    }

    private function filters(Request $request, bool $withFormat = false): array
    {
        $rules = [
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'branch' => ['nullable', 'string', 'max:255'],
        ];
        if ($withFormat) $rules['format'] = ['required', 'in:xlsx,csv'];
        return $request->validate($rules);
    }
}
