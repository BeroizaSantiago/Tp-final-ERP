<?php

namespace App\Http\Controllers\Api\Reports\Purchases;

use App\Exports\DetailedPurchasesReportExport;
use App\Http\Controllers\Controller;
use App\Services\Reports\Purchases\DetailedPurchasesReportService;
use App\Services\Reports\ReportExportService;
use Illuminate\Http\Request;

class DetailedPurchasesReportController extends Controller
{
    public function __construct(private readonly DetailedPurchasesReportService $service, private readonly ReportExportService $exports) {}
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
        return $this->exports->pdf('reports.purchases.detailed.pdf', compact('report'), 'detalle-compras-' . now()->format('Ymd-His') . '.pdf');
    }
    public function export(Request $r)
    {
        $f = $this->filters($r, true);
        $format = $f['format'];
        unset($f['format']);
        $report = $this->service->generate($f);
        return $this->exports->spreadsheet(new DetailedPurchasesReportExport($this->service->exportRows($report)), 'detalle-compras-' . now()->format('Ymd-His') . '.' . $format, $format);
    }
    private function filters(Request $r, bool $e = false): array
    {
        $rules = ['date_from' => ['required', 'date'], 'date_to' => ['required', 'date', 'after_or_equal:date_from'], 'branches' => ['nullable', 'array'], 'branches.*' => ['string', 'max:255'], 'provider_ids' => ['nullable', 'array'], 'provider_ids.*' => ['integer', 'exists:providers,id']];
        if ($e) $rules['format'] = ['required', 'in:xlsx,csv'];
        return $r->validate($rules);
    }
}
