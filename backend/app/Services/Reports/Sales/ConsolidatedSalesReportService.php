<?php

namespace App\Services\Reports\Sales;

use App\Models\Finance\CashSheet;
use App\Models\Products\Product;
use App\Models\Products\ProductCategory;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoiceItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Prepara los datos del Reporte de Ventas Consolidado.
 *
 * Centraliza filtros, agrupaciones y cálculos para que la consulta en pantalla,
 * PDF, Excel y CSV compartan exactamente la misma fuente de información.
 */
class ConsolidatedSalesReportService
{
    public function generate(array $filters): array
    {
        $documents = $this->documents($filters)->get();
        $sales = $documents->filter(fn (Invoice $invoice) => $this->documentKind($invoice) === 'sale');
        $creditNotes = $documents->filter(fn (Invoice $invoice) => $this->documentKind($invoice) === 'credit_note');
        $debitNotes = $documents->filter(fn (Invoice $invoice) => $this->documentKind($invoice) === 'debit_note');

        $salesTotal = $this->amount($sales);
        $creditTotal = $this->amount($creditNotes);
        $debitTotal = $this->amount($debitNotes);
        $operations = $sales->count();
        $units = round((float) $sales->flatMap->items->sum('quantity'), 4);
        $itemContext = $this->itemContext($sales->flatMap->items);

        return [
            'filters' => [
                'date_from' => $filters['date_from'],
                'date_to' => $filters['date_to'],
                'branch' => $filters['branch'] ?? null,
            ],
            'summary' => [
                'operations' => $operations,
                'units' => $units,
                'sales_total' => $salesTotal,
                'average_ticket' => $operations ? round($salesTotal / $operations, 2) : 0.0,
                'credit_notes_total' => $creditTotal,
                'debit_notes_total' => $debitTotal,
                'grand_total' => round($salesTotal - $creditTotal + $debitTotal, 2),
            ],
            'by_branch' => $this->byBranch($sales),
            'by_category' => $this->byItemDimension($sales, 'category', $salesTotal, $itemContext),
            'by_brand' => $this->byItemDimension($sales, 'brand', $salesTotal, $itemContext),
            'by_payment_method' => $this->byPaymentMethod($sales),
            'by_channel' => $this->byChannel($sales),
        ];
    }

    public function branches(): Collection
    {
        return CashSheet::query()
            ->whereNotNull('branch_name')
            ->where('branch_name', '!=', '')
            ->distinct()
            ->orderBy('branch_name')
            ->pluck('branch_name')
            ->values();
    }

    public function exportRows(array $report): Collection
    {
        $rows = collect([
            $this->row('Resumen de Ventas', 'Cantidad de operaciones', $report['summary']['operations']),
            $this->row('Resumen de Ventas', 'Unidades vendidas', $report['summary']['units']),
            $this->row('Resumen de Ventas', 'Ventas totales', null, $report['summary']['sales_total']),
            $this->row('Resumen de Ventas', 'Ticket promedio', null, $report['summary']['average_ticket']),
            $this->row('Resumen de Ventas', 'Notas de crédito (-)', null, -$report['summary']['credit_notes_total']),
            $this->row('Resumen de Ventas', 'Notas de débito (+)', null, $report['summary']['debit_notes_total']),
            $this->row('Resumen de Ventas', 'Total general', null, $report['summary']['grand_total']),
        ]);

        foreach ($report['by_branch'] as $item) {
            $rows->push($this->row('Ventas por Sucursal', $item['name'], $item['operations'], $item['total'], null, $item['units'], $item['average']));
        }
        foreach ($report['by_category'] as $item) {
            $rows->push($this->row('Ventas por Categoría', $item['name'], null, $item['total'], $item['percentage'], $item['units']));
        }
        foreach ($report['by_brand'] as $item) {
            $rows->push($this->row('Ventas por Marca', $item['name'], null, $item['total'], $item['percentage'], $item['units']));
        }
        foreach ($report['by_payment_method'] as $item) {
            $rows->push($this->row('Ventas por Medio de Pago', $item['name'], $item['operations'], $item['total']));
        }
        foreach ($report['by_channel'] as $item) {
            $rows->push($this->row('Ventas por Canal', $item['name'], $item['operations'], $item['total']));
        }

        return $rows;
    }

    private function documents(array $filters): Builder
    {
        $branchSubquery = DB::table('cash_sheet_movements as report_movements')
            ->join('cash_sheets as report_sheets', 'report_sheets.id', '=', 'report_movements.cash_sheet_id')
            ->select('report_sheets.branch_name')
            ->whereColumn('report_movements.invoice_id', 'invoices.id')
            ->whereNotNull('report_sheets.branch_name')
            ->orderBy('report_movements.id')
            ->limit(1);

        return Invoice::query()
            ->select('invoices.*')
            ->selectSub($branchSubquery, 'report_branch_name')
            ->with([
                'items.variant.product.category:id,name',
                'items.variant.product.brand:id,name',
                'payments:id,invoice_id,payment_method,amount,total_paid',
            ])
            ->whereDate('issue_date', '>=', $filters['date_from'])
            ->whereDate('issue_date', '<=', $filters['date_to'])
            ->where(fn (Builder $query) => $query
                ->whereIn('receipt_types_prefix', ['FV', 'NC', 'ND'])
                ->orWhere(fn (Builder $legacy) => $legacy
                    ->whereNull('receipt_types_prefix')
                    ->where(fn (Builder $types) => $types
                        ->where('receipt_type_name', 'like', '%Factura%')
                        ->orWhere('receipt_type_name', 'like', '%Nota de Crédito%')
                        ->orWhere('receipt_type_name', 'like', '%Nota de Credito%')
                        ->orWhere('receipt_type_name', 'like', '%Nota de Débito%')
                        ->orWhere('receipt_type_name', 'like', '%Nota de Debito%'))))
            ->whereRaw("LOWER(COALESCE(status_name, '')) NOT LIKE '%anul%'")
            ->when($filters['branch'] ?? null, fn (Builder $query, string $branch) => $query->whereExists(
                fn ($exists) => $exists->selectRaw('1')
                    ->from('cash_sheet_movements as filter_movements')
                    ->join('cash_sheets as filter_sheets', 'filter_sheets.id', '=', 'filter_movements.cash_sheet_id')
                    ->whereColumn('filter_movements.invoice_id', 'invoices.id')
                    ->where('filter_sheets.branch_name', $branch)
            ))
            ->orderBy('issue_date')
            ->orderBy('id');
    }

    private function byBranch(Collection $sales): array
    {
        return $sales->groupBy(fn (Invoice $invoice) => $invoice->report_branch_name ?: 'Sin sucursal')
            ->map(function (Collection $invoices, string $name) {
                $total = $this->amount($invoices);
                $operations = $invoices->count();
                return [
                    'name' => $name,
                    'operations' => $operations,
                    'units' => round((float) $invoices->flatMap->items->sum('quantity'), 4),
                    'total' => $total,
                    'average' => $operations ? round($total / $operations, 2) : 0.0,
                ];
            })->sortByDesc('total')->values()->all();
    }

    private function byItemDimension(Collection $sales, string $dimension, float $salesTotal, array $context): array
    {
        return $sales->flatMap->items
            ->groupBy(function (InvoiceItem $item) use ($dimension, $context) {
                $product = $item->variant?->product
                    ?? $context['products_by_external']->get((string) $item->product_external_id)
                    ?? $context['products_by_name']->get(mb_strtolower((string) $item->product_name));

                if ($dimension === 'category') {
                    return $item->product_category_name
                        ?: $context['categories']->get((int) $item->product_category_id)
                        ?: $product?->getRelation('category')?->name
                        ?: $product?->getAttribute('category')
                        ?: 'Sin categoría';
                }
                return $item->brand_name
                    ?: $product?->getRelation('brand')?->name
                    ?: $product?->getAttribute('brand')
                    ?: 'Sin marca';
            })
            ->map(function (Collection $items, string $name) use ($salesTotal) {
                $total = round((float) $items->sum(fn (InvoiceItem $item) => $this->itemTotal($item)), 2);
                return [
                    'name' => $name,
                    'units' => round((float) $items->sum('quantity'), 4),
                    'total' => $total,
                    'percentage' => $salesTotal > 0 ? round($total * 100 / $salesTotal, 2) : 0.0,
                ];
            })->sortByDesc('total')->values()->all();
    }

    /** Resuelve también productos de ventas históricas que no guardaron variante. */
    private function itemContext(Collection $items): array
    {
        $externalIds = $items->pluck('product_external_id')->filter()->unique()->values();
        $names = $items->pluck('product_name')->filter()->unique()->values();
        $categoryIds = $items->pluck('product_category_id')->filter()->unique()->values();

        $products = Product::query()->with(['category', 'brand'])
            ->where(function (Builder $query) use ($externalIds, $names) {
                if ($externalIds->isNotEmpty()) $query->whereIn('external_id', $externalIds);
                if ($names->isNotEmpty()) {
                    $method = $externalIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('name', $names);
                }
            })->get();

        return [
            'products_by_external' => $products->filter(fn (Product $product) => $product->external_id !== null)
                ->keyBy(fn (Product $product) => (string) $product->external_id),
            'products_by_name' => $products->keyBy(fn (Product $product) => mb_strtolower((string) $product->name)),
            'categories' => ProductCategory::query()->whereIn('id', $categoryIds)->pluck('name', 'id'),
        ];
    }

    private function byPaymentMethod(Collection $sales): array
    {
        return $sales->flatMap(fn (Invoice $invoice) => $invoice->payments->map(fn ($payment) => [
            'invoice_id' => $invoice->id,
            'method' => $this->paymentLabel($payment->payment_method),
            'amount' => (float) ($payment->payment_method === 'current_account' ? $payment->amount : $payment->total_paid),
        ]))->groupBy('method')->map(fn (Collection $payments, string $name) => [
            'name' => $name,
            'operations' => $payments->pluck('invoice_id')->unique()->count(),
            'total' => round((float) $payments->sum('amount'), 2),
        ])->sortByDesc('total')->values()->all();
    }

    private function byChannel(Collection $sales): array
    {
        return $sales->groupBy(fn (Invoice $invoice) => $this->channelLabel($invoice))
            ->map(fn (Collection $invoices, string $name) => [
                'name' => $name,
                'operations' => $invoices->count(),
                'total' => $this->amount($invoices),
            ])->sortByDesc('total')->values()->all();
    }

    private function documentKind(Invoice $invoice): string
    {
        $type = mb_strtolower((string) $invoice->receipt_type_name);
        if ($invoice->receipt_types_prefix === 'NC' || str_contains($type, 'crédito') || str_contains($type, 'credito')) return 'credit_note';
        if ($invoice->receipt_types_prefix === 'ND' || str_contains($type, 'débito') || str_contains($type, 'debito')) return 'debit_note';
        return 'sale';
    }

    private function itemTotal(InvoiceItem $item): float
    {
        $stored = (float) $item->total_amount;
        return $stored ?: (float) $item->quantity * (float) ($item->unit_price_with_taxes ?: $item->unit_price);
    }

    private function amount(Collection $documents): float
    {
        return round((float) $documents->sum('total_amount'), 2);
    }

    private function paymentLabel(?string $method): string
    {
        return match ($method) {
            'cash' => 'Efectivo', 'credit_card' => 'Tarjeta de crédito',
            'debit_card' => 'Tarjeta de débito', 'transfer' => 'Transferencia',
            'current_account', 'checking_account' => 'Cuenta corriente',
            'voucher' => 'Voucher',
            default => $method ? ucfirst(str_replace('_', ' ', $method)) : 'Sin informar',
        };
    }

    private function channelLabel(Invoice $invoice): string
    {
        if ($invoice->service_channel) return $invoice->service_channel;
        if ($invoice->ecommerce_number) return 'E-commerce';
        return match ((int) $invoice->service_channel_id) {
            2 => 'E-commerce', 3 => 'Aplicación móvil', default => 'ERP',
        };
    }

    private function row(string $section, string $concept, $operations = null, $amount = null, $percentage = null, $units = null, $average = null): array
    {
        return compact('section', 'concept', 'operations', 'units', 'amount', 'average', 'percentage');
    }
}
