<?php

namespace App\Http\Controllers\Api\Reports\CompanyPosition;

use App\Exports\EconomicResultReportExport;
use App\Exports\FinancialResultReportExport;
use App\Http\Controllers\Controller;
use App\Services\Reports\CompanyPosition\EconomicResultReportService;
use App\Services\Reports\CompanyPosition\FinancialResultReportService;
use App\Services\Reports\ReportExportService;
use Illuminate\Http\Request;

/** Entrega los resultados económico y financiero sin realizar cálculos. */
class CompanyPositionReportController extends Controller
{
    public function __construct(private readonly EconomicResultReportService $economic, private readonly FinancialResultReportService $financial, private readonly ReportExportService $exports) {}
    public function options()
    {
        return response()->json($this->economic->options());
    }
    public function economic(Request $request)
    {
        return response()->json($this->economic->generate($this->filters($request)));
    }
    public function economicPdf(Request $request)
    {
        $report = $this->economic->generate($this->filters($request));
        return $this->exports->pdf('reports.company-position.economic.pdf', compact('report'), 'resultado-economico.pdf');
    }
    public function economicExport(Request $request)
    {
        $filters = $this->filters($request, true);
        $format = $filters['format'];
        unset($filters['format']);
        $report = $this->economic->generate($filters);
        return $this->exports->spreadsheet(new EconomicResultReportExport($this->economic->exportRows($report)), 'resultado-economico.' . $format, $format);
    }
    public function financial(Request $request)
    {
        return response()->json($this->financial->generate($this->filters($request)));
    }
    public function financialPdf(Request $request)
    {
        $report = $this->financial->generate($this->filters($request));
        return $this->exports->pdf('reports.company-position.financial.pdf', compact('report'), 'resultado-financiero.pdf');
    }
    public function financialExport(Request $request)
    {
        $filters = $this->filters($request, true);
        $format = $filters['format'];
        unset($filters['format']);
        $report = $this->financial->generate($filters);
        return $this->exports->spreadsheet(new FinancialResultReportExport($this->financial->exportRows($report)), 'resultado-financiero.' . $format, $format);
    }
    private function filters(Request $request, bool $export = false): array
    {
        $rules = ['date_from' => ['required', 'date'], 'date_to' => ['required', 'date', 'after_or_equal:date_from'], 'branch' => ['nullable', 'string', 'max:255']];
        if ($export) $rules['format'] = ['required', 'in:xlsx,csv'];
        return $request->validate($rules);
    }
}
