<?php

namespace App\Services\Reports\Sales;

use App\Models\Clients\Client;
use App\Models\Finance\CashSheet;
use App\Models\Products\Product;
use App\Models\Purchases\PurchaseItem;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoiceItem;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Calcula el Listado de Comisiones de vendedores.
 *
 * El criterio elegido es excluyente: Venta, Cobranza o Ganancia. La misma
 * colección resultante alimenta el PDF, Excel y CSV.
 */
class SalesCommissionReportService
{
    public function generate(array $filters): array
    {
        $invoices = $this->invoices($filters)->get();
        $users = User::query()->get()->keyBy('id');
        $usersByIdentity = collect();
        $users->each(function (User $user) use ($usersByIdentity) {
            foreach ([$user->name, $user->username, $user->email] as $identity) {
                if ($identity) {
                    $usersByIdentity->put(mb_strtolower((string) $identity), $user);
                }
            }
        });
        $costHistory = $this->costHistory($invoices->flatMap->items);

        $rows = $invoices->map(function (Invoice $invoice) use ($filters, $users, $usersByIdentity, $costHistory) {
            $seller = $this->seller($invoice, $users, $usersByIdentity);
            $saleAmount = round((float) $invoice->total_amount, 2);
            $netAmount = $this->netAmount($invoice);
            $collected = $this->collectedAmount($invoice, $filters['date_from'], $filters['date_to']);
            $cost = $this->invoiceCost($invoice, $costHistory);
            $profit = round(max(0, $saleAmount - $cost), 2);

            $saleCommission = 0.0;
            $collectionCommission = 0.0;
            $profitCommission = 0.0;

            if ($filters['criterion'] === 'sale') {
                $base = $filters['gross_sale_commission'] ? $saleAmount : $netAmount;
                $saleCommission = $this->percentage($base, $seller?->sales_commission_percentage);
            } elseif ($filters['criterion'] === 'collection') {
                $collectionCommission = $this->percentage($collected, $seller?->collections_commission_percentage);
            } elseif ($filters['criterion'] === 'profit') {
                $profitCommission = $this->percentage($profit, $seller?->profit_commission_percentage);
            }

            return [
                'seller_id' => $seller?->id,
                'seller' => $seller?->name ?: $invoice->seller_full_name ?: $invoice->user_name ?: 'Sin vendedor',
                'issue_date' => optional($invoice->issue_date)->format('d/m/Y'),
                'receipt_type' => $invoice->receipt_type_name ?: 'Factura',
                'number' => $invoice->full_number ?: 'Venta #' . $invoice->id,
                'client' => $invoice->client?->name ?: $invoice->customer_name ?: 'Consumidor final',
                'point_of_sale' => $this->pointOfSale($invoice),
                'sale_amount' => $saleAmount,
                'net_amount' => $netAmount,
                'collected_amount' => $collected,
                'cost_amount' => $cost,
                'profit_amount' => $profit,
                'collection_commission' => $collectionCommission,
                'sale_commission' => $saleCommission,
                'profit_commission' => $profitCommission,
                'total_commission' => round($saleCommission + $collectionCommission + $profitCommission, 2),
            ];
        })->when($filters['seller_id'] ?? null, fn (Collection $rows, $sellerId) => $rows->where('seller_id', (int) $sellerId))
          ->when($filters['point_of_sale'] ?? null, fn (Collection $rows, $pos) => $rows->where('point_of_sale', $pos))
          ->values();

        return [
            'filters' => $filters,
            'criterion_label' => match ($filters['criterion']) {
                'sale' => 'Venta', 'collection' => 'Cobranza', 'profit' => 'Ganancia',
            },
            'rows' => $rows->all(),
            'summary' => [
                'records' => $rows->count(),
                'sale_amount' => round((float) $rows->sum('sale_amount'), 2),
                'collected_amount' => round((float) $rows->sum('collected_amount'), 2),
                'collection_commission' => round((float) $rows->sum('collection_commission'), 2),
                'sale_commission' => round((float) $rows->sum('sale_commission'), 2),
                'profit_commission' => round((float) $rows->sum('profit_commission'), 2),
                'total_commission' => round((float) $rows->sum('total_commission'), 2),
            ],
        ];
    }

    public function options(): array
    {
        return [
            'sellers' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'username']),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name', 'document_number']),
            'points_of_sale' => CashSheet::query()->whereNotNull('pos_name')->where('pos_name', '!=', '')
                ->distinct()->pluck('pos_name')
                ->merge(Invoice::query()->whereNotNull('arca_point_of_sale')->distinct()->pluck('arca_point_of_sale')->map(fn ($number) => 'Punto ' . $number))
                ->merge(Invoice::query()->whereNotNull('first_number')->where('first_number', '!=', '')->distinct()->pluck('first_number')->map(fn ($number) => 'Punto ' . $number))
                ->filter()->unique()->sort()->values(),
        ];
    }

    public function exportRows(array $report): Collection
    {
        return collect($report['rows'])->map(fn (array $row) => [
            $row['seller'], $row['issue_date'], $row['receipt_type'], $row['number'], $row['client'],
            $row['point_of_sale'], $row['sale_amount'], $row['collected_amount'],
            $row['collection_commission'], $row['sale_commission'], $row['profit_commission'], $row['total_commission'],
        ]);
    }

    private function invoices(array $filters): Builder
    {
        $sellerSubquery = DB::table('cash_sheet_movements as commission_movements')
            ->select('commission_movements.user_id')
            ->whereColumn('commission_movements.invoice_id', 'invoices.id')
            ->whereNotNull('commission_movements.user_id')->orderBy('commission_movements.id')->limit(1);
        $posSubquery = DB::table('cash_sheet_movements as commission_pos_movements')
            ->join('cash_sheets as commission_sheets', 'commission_sheets.id', '=', 'commission_pos_movements.cash_sheet_id')
            ->select('commission_sheets.pos_name')
            ->whereColumn('commission_pos_movements.invoice_id', 'invoices.id')
            ->whereNotNull('commission_sheets.pos_name')->orderBy('commission_pos_movements.id')->limit(1);

        return Invoice::query()->select('invoices.*')
            ->selectSub($sellerSubquery, 'report_seller_user_id')
            ->selectSub($posSubquery, 'report_point_of_sale')
            ->with(['client:id,name,document_number', 'items.variant.product', 'payments', 'receiptApplications.receipt'])
            ->where(fn (Builder $query) => $query->where('receipt_types_prefix', 'FV')
                ->orWhere(fn (Builder $legacy) => $legacy->whereNull('receipt_types_prefix')->where('receipt_type_name', 'like', '%Factura%')))
            ->whereRaw("LOWER(COALESCE(status_name, '')) NOT LIKE '%anul%'")
            ->when($filters['client_id'] ?? null, fn (Builder $query, $clientId) => $query->where('client_id', $clientId))
            ->when($filters['criterion'] !== 'collection', fn (Builder $query) => $query
                ->whereDate('issue_date', '>=', $filters['date_from'])->whereDate('issue_date', '<=', $filters['date_to']))
            ->when($filters['criterion'] === 'collection', fn (Builder $query) => $query->where(function (Builder $collected) use ($filters) {
                $collected->whereHas('payments', fn (Builder $payments) => $payments
                    ->where('payment_method', '!=', 'current_account')->where('total_paid', '>', 0)
                    ->whereDate('created_at', '>=', $filters['date_from'])->whereDate('created_at', '<=', $filters['date_to']))
                    ->orWhereHas('receiptApplications.receipt', fn (Builder $receipts) => $receipts
                        ->where('status', '!=', 'cancelled')->whereDate('receipt_date', '>=', $filters['date_from'])
                        ->whereDate('receipt_date', '<=', $filters['date_to']));
            }))->orderBy('issue_date')->orderBy('id');
    }

    private function seller(Invoice $invoice, Collection $users, Collection $usersByIdentity): ?User
    {
        if ($invoice->report_seller_user_id && $users->has((int) $invoice->report_seller_user_id)) {
            return $users->get((int) $invoice->report_seller_user_id);
        }
        foreach ([$invoice->user_name, $invoice->seller_full_name] as $identity) {
            if ($identity && $usersByIdentity->has(mb_strtolower($identity))) return $usersByIdentity->get(mb_strtolower($identity));
        }
        return null;
    }

    private function collectedAmount(Invoice $invoice, string $from, string $to): float
    {
        $direct = $invoice->payments->filter(fn ($payment) => $payment->payment_method !== 'current_account'
            && (float) $payment->total_paid > 0 && $this->within($payment->created_at, $from, $to))->sum('total_paid');
        $receipts = $invoice->receiptApplications->filter(fn ($application) => $application->receipt
            && $application->receipt->status !== 'cancelled'
            && $this->within($application->receipt->receipt_date, $from, $to))->sum('amount');
        return round((float) $direct + (float) $receipts, 2);
    }

    private function netAmount(Invoice $invoice): float
    {
        $stored = (float) $invoice->taxed_amount + (float) $invoice->non_taxed_amount + (float) $invoice->exempt_amount;
        return round($stored > 0 ? $stored : (float) $invoice->items->sum('subtotal_amount'), 2);
    }

    private function costHistory(Collection $items): Collection
    {
        $variantIds = $items->pluck('product_variant_id')->filter()->unique();
        $productIds = $items->map(fn (InvoiceItem $item) => $item->variant?->product_id)->filter()->unique();
        $names = $items->pluck('product_name')->filter()->unique();

        return PurchaseItem::query()->with('purchase:id,issue_date')
            ->whereHas('purchase')->where(function (Builder $query) use ($variantIds, $productIds, $names) {
                if ($variantIds->isNotEmpty()) $query->whereIn('product_variant_id', $variantIds);
                if ($productIds->isNotEmpty()) $query->orWhereIn('product_id', $productIds);
                if ($names->isNotEmpty()) $query->orWhereIn('product_name', $names);
            })->get();
    }

    private function invoiceCost(Invoice $invoice, Collection $history): float
    {
        return round((float) $invoice->items->sum(function (InvoiceItem $item) use ($invoice, $history) {
            $product = $item->variant?->product;
            $purchase = $history->filter(fn (PurchaseItem $candidate) => $candidate->purchase?->issue_date
                && $candidate->purchase->issue_date->lte($invoice->issue_date)
                && (($item->product_variant_id && (int) $candidate->product_variant_id === (int) $item->product_variant_id)
                    || ($product && (int) $candidate->product_id === (int) $product->id)
                    || ($item->product_name && mb_strtolower((string) $candidate->product_name) === mb_strtolower((string) $item->product_name))))
                ->sortByDesc(fn (PurchaseItem $candidate) => $candidate->purchase->issue_date)->first();
            $unitCost = $purchase?->unit_price;
            if ($unitCost === null && $product) {
                $unitCost = $product->cost_with_discount ?: $product->replacement_cost ?: $product->last_purchase_price ?: 0;
            }
            return (float) $unitCost * (float) $item->quantity;
        }), 2);
    }

    private function pointOfSale(Invoice $invoice): string
    {
        return $invoice->report_point_of_sale ?: ($invoice->arca_point_of_sale ? 'Punto ' . $invoice->arca_point_of_sale : ($invoice->first_number ? 'Punto ' . $invoice->first_number : 'Sin informar'));
    }

    private function percentage(float $base, $percentage): float
    {
        return round($base * (float) ($percentage ?? 0) / 100, 2);
    }

    private function within(?CarbonInterface $date, string $from, string $to): bool
    {
        return $date && $date->toDateString() >= $from && $date->toDateString() <= $to;
    }
}
