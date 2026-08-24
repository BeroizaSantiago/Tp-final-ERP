<?php

namespace App\Services\Reports\CompanyPosition;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Calcula cobros y pagos efectivos sin contabilizar traspasos internos de caja. */
class FinancialResultReportService
{
    public function __construct(private readonly CompanyPositionReportSupport $support) {}

    public function generate(array $filters): array
    {
        $periods = $this->support->periods($filters['date_from'], $filters['date_to']);
        $rows = $periods->map(fn ($period) => ['period' => $period['label'], 'collections' => 0.0, 'payments' => 0.0, 'result' => 0.0]);

        foreach ($this->cashMovements($filters)->get() as $movement) {
            $key = $this->support->key($movement->movement_date, $periods);
            if (! $rows->has($key)) continue;
            $column = $movement->movement_type === 'income' ? 'collections' : 'payments';
            $row=$rows->get($key); $row[$column]+=(float)$movement->amount; $rows->put($key,$row);
        }
        foreach ($this->bankMovements($filters)->get() as $movement) {
            $kind = $this->bankKind($movement->movement_type_name, $movement->bank_concept_name);
            if (! $kind) continue;
            $key = $this->support->key($movement->movement_date, $periods);
            if ($rows->has($key)) { $row=$rows->get($key); $row[$kind]+=abs((float)$movement->amount); $rows->put($key,$row); }
        }
        foreach ($this->miscExpensePayments($filters)->get() as $payment) {
            $key = $this->support->key($payment->movement_date, $periods);
            if ($rows->has($key)) { $row=$rows->get($key); $row['payments']+=(float)$payment->total_paid; $rows->put($key,$row); }
        }

        $rows = $rows->map(function (array $row) {
            $row['collections'] = round($row['collections'], 2); $row['payments'] = round($row['payments'], 2);
            $row['result'] = round($row['collections'] - $row['payments'], 2);
            return $row;
        })->values();

        return [
            'filters' => $filters, 'rows' => $rows->all(),
            'summary' => [
                'collections' => round((float) $rows->sum('collections'), 2),
                'payments' => round((float) $rows->sum('payments'), 2),
                'result' => round((float) $rows->sum('result'), 2),
            ],
            'chart' => $this->support->chart($rows, 'collections', 'payments', 'Cobros', 'Pagos'),
        ];
    }

    public function options(): array { return ['branches' => $this->support->branches()]; }
    public function exportRows(array $report): Collection { return collect($report['rows'])->map(fn ($row) => array_values($row)); }

    private function cashMovements(array $filters)
    {
        return DB::table('cash_sheet_movements as m')->join('cash_sheets as s', 's.id', '=', 'm.cash_sheet_id')
            ->selectRaw('m.created_at as movement_date, m.movement_type, m.amount')
            ->where('m.status', 'active')->whereNull('m.voided_at')->whereNull('m.origin_cash_sheet_id')
            ->whereIn('m.movement_type', ['income', 'expense'])->where('m.payment_method', '!=', 'cash_closure')
            // Las tarjetas de crédito impactan al conciliarse en Bancos, no dos veces.
            ->where('m.payment_method', '!=', 'credit_card')
            ->whereDate('m.created_at', '>=', $filters['date_from'])->whereDate('m.created_at', '<=', $filters['date_to'])
            ->when($filters['branch'] ?? null, fn ($query, $branch) => $query->where('s.branch_name', $branch));
    }

    private function bankMovements(array $filters)
    {
        return DB::table('bank_movements')->selectRaw('COALESCE(issue_date, creation_date, created_at) as movement_date, movement_type_name, bank_concept_name, amount')
            ->where('is_active', true)->whereNull('deleted_date')
            ->whereDate(DB::raw('COALESCE(issue_date, creation_date, created_at)'), '>=', $filters['date_from'])
            ->whereDate(DB::raw('COALESCE(issue_date, creation_date, created_at)'), '<=', $filters['date_to'])
            ->when($filters['branch'] ?? null, fn ($query, $branch) => $query->where('branch_name', $branch));
    }

    private function miscExpensePayments(array $filters)
    {
        return DB::table('misc_expense_payments as p')->join('misc_expenses as e', 'e.id', '=', 'p.misc_expense_id')
            ->selectRaw('p.created_at as movement_date, p.total_paid')->whereRaw("LOWER(COALESCE(e.status_name,'')) NOT LIKE '%anul%'")
            ->whereDate('p.created_at', '>=', $filters['date_from'])->whereDate('p.created_at', '<=', $filters['date_to'])
            ->when($filters['branch'] ?? null, fn ($query, $branch) => $query->where('e.branch_name', $branch));
    }

    private function bankKind(?string $movement, ?string $concept): ?string
    {
        $text = mb_strtolower(trim($movement.' '.$concept));
        if (preg_match('/ingreso|dep[oó]sito|acreditaci[oó]n|cobro/', $text)) return 'collections';
        if (preg_match('/egreso|extracci[oó]n|d[eé]bito|pago/', $text)) return 'payments';
        return null; // Transferencias internas y conceptos sin dirección no alteran el resultado.
    }
}
