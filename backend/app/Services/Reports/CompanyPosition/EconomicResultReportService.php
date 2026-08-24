<?php

namespace App\Services\Reports\CompanyPosition;

use App\Models\Purchases\MiscExpense;
use App\Models\Purchases\Purchase;
use App\Models\Sales\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Calcula ventas, compras y gastos devengados por período. */
class EconomicResultReportService
{
    public function __construct(private readonly CompanyPositionReportSupport $support) {}

    public function generate(array $filters): array
    {
        $periods = $this->support->periods($filters['date_from'], $filters['date_to']);
        $rows = $periods->map(fn ($period) => [
            'period' => $period['label'], 'sales' => 0.0, 'expenses' => 0.0, 'purchases' => 0.0,
            'result' => 0.0, 'profitability' => 0.0,
        ]);

        foreach ($this->sales($filters)->get() as $document) {
            $key = $this->support->key($document->issue_date, $periods);
            if ($rows->has($key)) { $row=$rows->get($key); $row['sales']+=$this->signedNet($document); $rows->put($key,$row); }
        }
        foreach ($this->purchases($filters)->get() as $document) {
            $key = $this->support->key($document->issue_date, $periods);
            if ($rows->has($key)) { $row=$rows->get($key); $row['purchases']+=$this->signedNet($document); $rows->put($key,$row); }
        }
        foreach ($this->expenses($filters)->get() as $expense) {
            $key = $this->support->key($expense->issue_date, $periods);
            if ($rows->has($key)) { $row=$rows->get($key); $row['expenses']+=$this->signedExpense($expense); $rows->put($key,$row); }
        }

        $rows = $rows->map(function (array $row) {
            foreach (['sales', 'expenses', 'purchases'] as $key) $row[$key] = round($row[$key], 2);
            $row['result'] = round($row['sales'] - $row['expenses'] - $row['purchases'], 2);
            $row['profitability'] = $row['sales'] != 0 ? round($row['result'] * 100 / $row['sales'], 2) : 0.0;
            return $row;
        })->values();

        $sales = round((float) $rows->sum('sales'), 2);
        $expenseTotal = round((float) $rows->sum('expenses'), 2);
        $purchaseTotal = round((float) $rows->sum('purchases'), 2);
        $expenses = round($expenseTotal + $purchaseTotal, 2);
        $result = round($sales - $expenses, 2);

        $chartRows = $rows->map(fn ($row) => $row + ['costs' => $row['expenses'] + $row['purchases']]);

        return [
            'filters' => $filters,
            'rows' => $rows->all(),
            'summary' => [
                'sales' => $sales, 'expenses' => $expenseTotal, 'purchases' => $purchaseTotal,
                'expenses_and_purchases' => $expenses, 'net_profit' => $result,
                'profitability' => $sales != 0 ? round($result * 100 / $sales, 2) : 0.0,
            ],
            'chart' => $this->support->chart($chartRows, 'sales', 'costs', 'Ventas Netas', 'Gastos / Compras Netas'),
        ];
    }

    public function options(): array { return ['branches' => $this->support->branches()]; }
    public function exportRows(array $report): Collection { return collect($report['rows'])->map(fn ($row) => array_values($row)); }

    private function sales(array $filters): Builder
    {
        return Invoice::query()->select('invoices.*')->selectRaw('(taxed_amount + non_taxed_amount + exempt_amount) as report_net')
            ->whereDate('issue_date', '>=', $filters['date_from'])->whereDate('issue_date', '<=', $filters['date_to'])
            ->where(fn ($query) => $query->whereIn('receipt_types_prefix', ['FV', 'NC', 'ND'])
                ->orWhere(fn ($legacy) => $legacy->whereNull('receipt_types_prefix')->where(fn ($types) => $types
                    ->where('receipt_type_name', 'like', '%Factura%')->orWhere('receipt_type_name', 'like', '%Nota de Crédito%')
                    ->orWhere('receipt_type_name', 'like', '%Nota de Credito%')->orWhere('receipt_type_name', 'like', '%Nota de Débito%')
                    ->orWhere('receipt_type_name', 'like', '%Nota de Debito%'))))
            ->whereRaw("LOWER(COALESCE(status_name,'')) NOT LIKE '%anul%'")
            ->when($filters['branch'] ?? null, fn ($query, $branch) => $query->whereExists(fn ($exists) => $exists->selectRaw('1')->from('cash_sheet_movements as m')->join('cash_sheets as s', 's.id', '=', 'm.cash_sheet_id')->whereColumn('m.invoice_id', 'invoices.id')->where('s.branch_name', $branch)));
    }

    private function purchases(array $filters): Builder
    {
        return Purchase::query()->select('purchases.*')->selectRaw('(taxed_amount + non_taxed_amount + exempt_amount) as report_net')
            ->whereDate('issue_date', '>=', $filters['date_from'])->whereDate('issue_date', '<=', $filters['date_to'])
            ->whereRaw("LOWER(COALESCE(status_name,'')) NOT LIKE '%anul%'")
            ->when($filters['branch'] ?? null, fn ($query, $branch) => $query->whereExists(fn ($exists) => $exists->selectRaw('1')->from('provider_payment_order_applications as a')->join('cash_sheet_movements as m', 'm.provider_payment_order_id', '=', 'a.provider_payment_order_id')->join('cash_sheets as s', 's.id', '=', 'm.cash_sheet_id')->whereColumn('a.purchase_id', 'purchases.id')->where('s.branch_name', $branch)));
    }

    private function expenses(array $filters): Builder
    {
        return MiscExpense::query()->whereDate('issue_date', '>=', $filters['date_from'])->whereDate('issue_date', '<=', $filters['date_to'])
            ->whereRaw("LOWER(COALESCE(status_name,'')) NOT LIKE '%anul%'")
            ->when($filters['branch'] ?? null, fn ($query, $branch) => $query->where('branch_name', $branch));
    }

    private function signedNet($document): float
    {
        $amount = (float) ($document->report_net ?: $document->total_amount);
        $type = mb_strtolower((string) $document->receipt_type_name);
        return $document->receipt_types_prefix === 'NC' || str_contains($type, 'crédito') || str_contains($type, 'credito') ? -abs($amount) : abs($amount);
    }

    private function signedExpense(MiscExpense $expense): float
    {
        $amount = (float) $expense->net_amount + (float) $expense->non_taxed_amount + (float) $expense->exempt_amount;
        $type = mb_strtolower((string) $expense->receipt_type_name);
        return str_contains($type, 'crédito') || str_contains($type, 'credito') ? -abs($amount) : abs($amount);
    }
}
