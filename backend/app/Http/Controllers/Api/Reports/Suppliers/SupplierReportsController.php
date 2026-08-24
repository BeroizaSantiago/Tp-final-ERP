<?php

namespace App\Http\Controllers\Api\Reports\Suppliers;

use App\Exports\SupplierCreditorsReportExport;
use App\Exports\SupplierListReportExport;
use App\Exports\SupplierRankingReportExport;
use App\Http\Controllers\Controller;
use App\Services\Reports\ReportExportService;
use App\Services\Reports\Suppliers\SupplierCreditorsReportService;
use App\Services\Reports\Suppliers\SupplierListReportService;
use App\Services\Reports\Suppliers\SupplierRankingReportService;
use Illuminate\Http\Request;

/** Expone los reportes de proveedores sin contener cálculos de negocio. */
class SupplierReportsController extends Controller
{
    public function __construct(
        private readonly SupplierListReportService $listService,
        private readonly SupplierRankingReportService $rankingService,
        private readonly SupplierCreditorsReportService $creditorsService,
        private readonly ReportExportService $exports,
    ) {}

    public function list()
    {
        return response()->json($this->listService->generate());
    }

    public function listPdf()
    {
        $report = $this->listService->generate();
        return $this->exports->pdf('reports.suppliers.list.pdf', compact('report'), 'proveedores.pdf');
    }

    public function listExport(Request $request)
    {
        $format = $request->validate(['format' => ['required', 'in:xlsx,csv']])['format'];
        $report = $this->listService->generate();
        return $this->exports->spreadsheet(
            new SupplierListReportExport($this->listService->exportRows($report)),
            'proveedores.'.$format,
            $format,
        );
    }

    public function ranking(Request $request)
    {
        return response()->json($this->rankingService->generate($this->rankingFilters($request)));
    }

    public function rankingPdf(Request $request)
    {
        $report = $this->rankingService->generate($this->rankingFilters($request));
        return $this->exports->pdf('reports.suppliers.ranking.pdf', compact('report'), 'ranking-proveedores.pdf');
    }

    public function rankingExport(Request $request)
    {
        $filters = $this->rankingFilters($request, true);
        $format = $filters['format'];
        unset($filters['format']);
        $report = $this->rankingService->generate($filters);

        return $this->exports->spreadsheet(
            new SupplierRankingReportExport($this->rankingService->exportRows($report)),
            'ranking-proveedores.'.$format,
            $format,
        );
    }

    public function creditors(Request $request)
    {
        return response()->json($this->creditorsService->generate($this->creditorFilters($request)));
    }

    public function creditorOptions()
    {
        return response()->json($this->creditorsService->options());
    }

    public function creditorsPdf(Request $request)
    {
        $report = $this->creditorsService->generate($this->creditorFilters($request));
        return $this->exports->pdf('reports.suppliers.creditors.pdf', compact('report'), 'acreedores.pdf');
    }

    public function creditorsExport(Request $request)
    {
        $filters = $this->creditorFilters($request, true);
        $format = $filters['format'];
        unset($filters['format']);
        $report = $this->creditorsService->generate($filters);

        return $this->exports->spreadsheet(
            new SupplierCreditorsReportExport($this->creditorsService->exportRows($report)),
            'acreedores.'.$format,
            $format,
        );
    }

    private function rankingFilters(Request $request, bool $export = false): array
    {
        $rules = [
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'top' => ['required', 'integer', 'min:1', 'max:500'],
        ];
        if ($export) {
            $rules['format'] = ['required', 'in:xlsx,csv'];
        }

        return $request->validate($rules);
    }

    private function creditorFilters(Request $request, bool $export = false): array
    {
        $rules = [
            'provider_ids' => ['nullable', 'array'],
            'provider_ids.*' => ['integer', 'exists:providers,id'],
            'debt_type' => ['required', 'in:expired,future,both'],
        ];
        if ($export) {
            $rules['format'] = ['required', 'in:xlsx,csv'];
        }

        return $request->validate($rules);
    }
}
