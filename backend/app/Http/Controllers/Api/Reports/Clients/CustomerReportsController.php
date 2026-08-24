<?php

namespace App\Http\Controllers\Api\Reports\Clients;

use App\Exports\CustomerDebtorsReportExport;
use App\Exports\CustomerListReportExport;
use App\Exports\CustomerRankingReportExport;
use App\Http\Controllers\Controller;
use App\Services\Reports\Clients\CustomerDebtorsReportService;
use App\Services\Reports\Clients\CustomerListReportService;
use App\Services\Reports\Clients\CustomerRankingReportService;
use App\Services\Reports\ReportExportService;
use Illuminate\Http\Request;

/** Intermediario de los reportes de clientes. */
class CustomerReportsController extends Controller
{
    public function __construct(private readonly CustomerListReportService $list, private readonly CustomerRankingReportService $ranking, private readonly CustomerDebtorsReportService $debtors, private readonly ReportExportService $exports) {}
    public function list(Request $r)
    {
        return response()->json($this->list->generate($this->listFilters($r)));
    }
    public function listOptions()
    {
        return response()->json($this->list->options());
    }
    public function listPdf(Request $r)
    {
        $report = $this->list->generate($this->listFilters($r));
        return $this->exports->pdf('reports.clients.list.pdf', compact('report'), 'clientes.pdf');
    }
    public function listExport(Request $r)
    {
        return $this->export($r, $this->list, new CustomerListReportExport(collect()), 'clientes', $this->listFilters($r, true));
    }
    public function ranking(Request $r)
    {
        return response()->json($this->ranking->generate($this->rankingFilters($r)));
    }
    public function rankingPdf(Request $r)
    {
        $report = $this->ranking->generate($this->rankingFilters($r));
        return $this->exports->pdf('reports.clients.ranking.pdf', compact('report'), 'ranking-clientes.pdf');
    }
    public function rankingExport(Request $r)
    {
        return $this->export($r, $this->ranking, new CustomerRankingReportExport(collect()), 'ranking-clientes', $this->rankingFilters($r, true));
    }
    public function debtors(Request $r)
    {
        return response()->json($this->debtors->generate($this->debtorFilters($r)));
    }
    public function debtorOptions()
    {
        return response()->json($this->debtors->options());
    }
    public function debtorsPdf(Request $r)
    {
        $report = $this->debtors->generate($this->debtorFilters($r));
        return $this->exports->pdf('reports.clients.debtors.pdf', compact('report'), 'deudores.pdf');
    }
    public function debtorsExport(Request $r)
    {
        return $this->export($r, $this->debtors, new CustomerDebtorsReportExport(collect()), 'deudores', $this->debtorFilters($r, true));
    }
    private function export(Request $r, $service, $prototype, string $name, array $f)
    {
        $format = $f['format'];
        unset($f['format']);
        $report = $service->generate($f);
        $class = $prototype::class;
        return $this->exports->spreadsheet(new $class($service->exportRows($report)), $name . '.' . $format, $format);
    }
    private function listFilters(Request $r, bool $e = false)
    {
        $rules = ['sellers' => ['nullable', 'array'], 'sellers.*' => ['string', 'max:150']];
        if ($e) $rules['format'] = ['required', 'in:xlsx,csv'];
        return $r->validate($rules);
    }
    private function rankingFilters(Request $r, bool $e = false)
    {
        $rules = ['date_from' => ['required', 'date'], 'date_to' => ['required', 'date', 'after_or_equal:date_from'], 'top' => ['required', 'integer', 'min:1', 'max:500'], 'order_by' => ['required', 'in:quantity,total']];
        if ($e) $rules['format'] = ['required', 'in:xlsx,csv'];
        return $r->validate($rules);
    }
    private function debtorFilters(Request $r, bool $e = false)
    {
        $rules = ['client_ids' => ['nullable', 'array'], 'client_ids.*' => ['integer', 'exists:clients,id'], 'sellers' => ['nullable', 'array'], 'sellers.*' => ['string', 'max:150'], 'provinces' => ['nullable', 'array'], 'provinces.*' => ['string', 'max:100'], 'debt_type' => ['required', 'in:expired,future,both']];
        if ($e) $rules['format'] = ['required', 'in:xlsx,csv'];
        return $r->validate($rules);
    }
}
