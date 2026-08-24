<?php

namespace App\Services\Reports\Purchases;

use App\Models\Purchases\ExpenseType;
use App\Models\Purchases\MiscExpense;
use Illuminate\Support\Collection;

/** Agrupa gastos varios por categoría y subcategoría. */
class MiscExpensesReportService
{
    public function generate(array $f): array
    {
        $expenses = MiscExpense::query()->with('expenseType:id,name,description')->whereDate('issue_date', '>=', $f['date_from'])->whereDate('issue_date', '<=', $f['date_to'])->whereRaw("LOWER(COALESCE(status_name,'')) NOT LIKE '%anul%'")->when($f['branches'] ?? null, fn($q, $v) => $q->whereIn('branch_name', $v))->when($f['expense_type_ids'] ?? null, fn($q, $v) => $q->whereIn('expense_type_id', $v))->get();
        $rows = $expenses->groupBy(fn($e) => ($e->expenseType?->name ?: 'Sin categoría') . '|' . ($e->expenseType?->description ?: 'Sin subcategoría'))->map(function ($items) {
            $first = $items->first();
            return ['category' => $first->expenseType?->name ?: 'Sin categoría', 'subcategory' => $first->expenseType?->description ?: 'Sin subcategoría', 'net' => round((float)$items->sum('net_amount'), 2), 'non_taxed' => round((float)$items->sum('non_taxed_amount'), 2), 'exempt' => round((float)$items->sum('exempt_amount'), 2), 'discount' => round((float)$items->sum('discount_amount'), 2), 'iva' => round((float)$items->sum('tax_amount'), 2), 'total' => round((float)$items->sum('total_amount'), 2), 'percentage' => 0.0];
        })->values();
        $total = round((float)$rows->sum('total'), 2);
        $rows = $rows->map(function ($r) use ($total) {
            $r['percentage'] = $total != 0 ? round($r['total'] * 100 / $total, 2) : 0;
            return $r;
        })->sortByDesc('total')->values();
        return ['filters' => $f, 'rows' => $rows->all(), 'summary' => ['net' => round((float)$rows->sum('net'), 2), 'non_taxed' => round((float)$rows->sum('non_taxed'), 2), 'exempt' => round((float)$rows->sum('exempt'), 2), 'discount' => round((float)$rows->sum('discount'), 2), 'iva' => round((float)$rows->sum('iva'), 2), 'total' => $total]];
    }
    public function options(): array
    {
        return ['branches' => MiscExpense::query()->whereNotNull('branch_name')->distinct()->orderBy('branch_name')->pluck('branch_name'), 'expense_types' => ExpenseType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])];
    }
    public function exportRows(array $r): Collection
    {
        return collect($r['rows'])->map(fn($x) => [$x['category'], $x['subcategory'], $x['net'], $x['non_taxed'], $x['exempt'], $x['discount'], $x['iva'], $x['total'], $x['percentage']]);
    }
}
