<?php

namespace App\Services\Reports\Sales;

use App\Models\Clients\Client;
use App\Models\Finance\CashSheet;
use App\Models\Purchases\PurchaseItem;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoiceItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Obtiene y normaliza todos los comprobantes del Listado de Ventas.
 * PDF, Excel, CSV y la respuesta JSON consumen el mismo resultado.
 */
class SalesListReportService
{
    public function generate(array $filters): array
    {
        $documents = $this->documents($filters)->get();
        $users = User::query()->get()->keyBy('id');
        $identities = $this->userIdentities($users);
        $costHistory = $this->costHistory($documents->flatMap->items);

        $rows = $documents->map(function (Invoice $invoice) use ($users, $identities, $costHistory) {
            $kind = $this->operationKey($invoice);
            $factor = $kind === 'credit_note' ? -1 : 1;
            $seller = $this->seller($invoice, $users, $identities);
            $net = (float) $invoice->taxed_amount;
            $nonTaxed = (float) $invoice->non_taxed_amount;
            $exempt = (float) $invoice->exempt_amount;
            if ($net + $nonTaxed + $exempt == 0) $net = (float) $invoice->items->sum('subtotal_amount');
            $iva = (float) $invoice->tax_amount ?: (float) $invoice->items->sum('tax_amount');
            $surcharges = (float) $invoice->net_recharge_amount + (float) $invoice->tax_recharge_amount;
            $discounts = (float) $invoice->net_global_discount_amount + (float) $invoice->tax_global_discount_amount
                + (float) $invoice->net_total_discount_product + (float) $invoice->tax_total_discount_product
                + (float) $invoice->promotion_discount_amount;
            $units = (float) $invoice->items->sum('quantity');
            $cost = $this->invoiceCost($invoice, $costHistory);
            $total = (float) $invoice->total_amount;

            return [
                'invoice_id' => $invoice->id, 'operation_key' => $kind,
                'date' => optional($invoice->issue_date)->format('d/m/Y'),
                'receipt_type' => $this->operationLabel($invoice), 'number' => $invoice->full_number ?: 'Comprobante #'.$invoice->id,
                'channel' => $this->channelLabel($invoice), 'branch' => $invoice->report_branch_name ?: 'Sin sucursal',
                'related_document' => $invoice->related_document_full_name ?: '—',
                'client' => $invoice->client?->name ?: $invoice->customer_name ?: 'Consumidor final',
                'client_document' => $invoice->client?->document_number ?: '—', 'province' => $invoice->client?->state ?: 'Sin informar',
                'seller_id' => $seller?->id, 'seller' => $seller?->name ?: $invoice->seller_full_name ?: $invoice->user_name ?: 'Sin vendedor',
                'price_type' => $invoice->client?->price_type ?: 'Sin informar', 'point_of_sale' => $this->pointOfSale($invoice),
                'net' => round($factor * $net, 2), 'non_taxed' => round($factor * $nonTaxed, 2),
                'exempt' => round($factor * $exempt, 2), 'surcharges' => round($factor * $surcharges, 2),
                'discounts' => round($factor * $discounts, 2), 'discount_percentage' => (float) $invoice->global_discount_percentage,
                'rounding' => 0.0, 'shipping_cost' => 0.0, 'iva' => round($factor * $iva, 2),
                'profit' => round($factor * ($total - $cost), 2), 'units' => round($factor * $units, 4),
                'total' => round($factor * $total, 2), 'payment_method' => $this->paymentMethods($invoice),
                'financing' => $this->financing($invoice),
            ];
        })->when($filters['operation_types'] ?? null, fn (Collection $rows, array $types) => $rows->whereIn('operation_key', $types))
          ->when($filters['branch'] ?? null, fn (Collection $rows, string $value) => $rows->where('branch', $value))
          ->when($filters['channel'] ?? null, fn (Collection $rows, string $value) => $rows->where('channel', $value))
          ->when($filters['province'] ?? null, fn (Collection $rows, string $value) => $rows->where('province', $value))
          ->when($filters['point_of_sale'] ?? null, fn (Collection $rows, string $value) => $rows->where('point_of_sale', $value))
          ->when($filters['seller_id'] ?? null, fn (Collection $rows, $value) => $rows->where('seller_id', (int) $value))
          ->when($filters['price_type'] ?? null, fn (Collection $rows, string $value) => $rows->where('price_type', $value))
          ->values();

        $total = round((float) $rows->sum('total'), 2);
        return ['filters' => $filters, 'rows' => $rows->all(), 'summary' => [
            'total' => $total, 'average_ticket' => $rows->count() ? round($total / $rows->count(), 2) : 0.0,
            'units' => round((float) $rows->sum('units'), 4), 'operations' => $rows->count(),
            'net' => round((float) $rows->sum('net'), 2), 'iva' => round((float) $rows->sum('iva'), 2),
            'profit' => round((float) $rows->sum('profit'), 2),
        ]];
    }

    public function exportRows(array $report): Collection
    {
        return collect($report['rows'])->map(fn (array $r) => [$r['date'], $r['receipt_type'], $r['number'], $r['channel'],
            $r['branch'], $r['related_document'], $r['client'], $r['client_document'], $r['province'], $r['seller'],
            $r['price_type'], $r['net'], $r['non_taxed'], $r['exempt'], $r['surcharges'], $r['discounts'],
            $r['discount_percentage'], $r['rounding'], $r['shipping_cost'], $r['iva'], $r['profit'], $r['units'],
            $r['total'], $r['payment_method'], $r['financing']]);
    }

    public function options(): array
    {
        $storedTypes = Invoice::query()->whereRaw("LOWER(COALESCE(receipt_type_name, '')) NOT LIKE '%compra%'")
            ->select(['receipt_types_prefix', 'receipt_type_name', 'is_bill_without_delivery'])->distinct()->get()
            ->map(fn (Invoice $invoice) => ['value' => $this->operationKey($invoice), 'label' => $this->operationLabel($invoice)])
            ->unique('value')->values();
        $operationTypes = collect([
            ['value' => 'invoice', 'label' => 'Factura de Venta'], ['value' => 'credit_note', 'label' => 'Nota de Crédito'],
            ['value' => 'debit_note', 'label' => 'Nota de Débito'], ['value' => 'delivery_note', 'label' => 'Remito de Venta'],
            ['value' => 'invoice_without_delivery', 'label' => 'Factura sin Entrega'], ['value' => 'customer_order', 'label' => 'Nota de Pedido'],
            ['value' => 'other', 'label' => 'Otros comprobantes'],
        ])->merge($storedTypes)->unique('value')->values();
        return [
            'operation_types' => $operationTypes,
            'branches' => CashSheet::query()->whereNotNull('branch_name')->where('branch_name', '!=', '')->distinct()->orderBy('branch_name')->pluck('branch_name'),
            'channels' => Invoice::query()->get(['service_channel', 'service_channel_id', 'ecommerce_number'])->map(fn ($i) => $this->channelLabel($i))->unique()->sort()->values(),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name', 'document_number']),
            'provinces' => Client::query()->whereNotNull('state')->where('state', '!=', '')->distinct()->orderBy('state')->pluck('state'),
            'points_of_sale' => $this->pointsOfSale(), 'sellers' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'username']),
            'price_types' => Client::query()->whereNotNull('price_type')->where('price_type', '!=', '')->distinct()->orderBy('price_type')->pluck('price_type'),
        ];
    }

    private function documents(array $filters): Builder
    {
        $branch = DB::table('cash_sheet_movements as list_movements')->join('cash_sheets as list_sheets', 'list_sheets.id', '=', 'list_movements.cash_sheet_id')
            ->select('list_sheets.branch_name')->whereColumn('list_movements.invoice_id', 'invoices.id')->whereNotNull('list_sheets.branch_name')->orderBy('list_movements.id')->limit(1);
        $seller = DB::table('cash_sheet_movements as list_seller_movements')->select('list_seller_movements.user_id')
            ->whereColumn('list_seller_movements.invoice_id', 'invoices.id')->whereNotNull('list_seller_movements.user_id')->orderBy('list_seller_movements.id')->limit(1);
        $pos = DB::table('cash_sheet_movements as list_pos_movements')->join('cash_sheets as list_pos_sheets', 'list_pos_sheets.id', '=', 'list_pos_movements.cash_sheet_id')
            ->select('list_pos_sheets.pos_name')->whereColumn('list_pos_movements.invoice_id', 'invoices.id')->whereNotNull('list_pos_sheets.pos_name')->orderBy('list_pos_movements.id')->limit(1);

        return Invoice::query()->select('invoices.*')->selectSub($branch, 'report_branch_name')->selectSub($seller, 'report_seller_user_id')->selectSub($pos, 'report_point_of_sale')
            ->with(['client:id,name,document_number,state,price_type', 'items.variant.product', 'payments:id,invoice_id,payment_method,amount,total_paid,card_plan,card_name,surcharge_amount'])
            ->whereDate('issue_date', '>=', $filters['date_from'])->whereDate('issue_date', '<=', $filters['date_to'])
            ->whereRaw("LOWER(COALESCE(receipt_type_name, '')) NOT LIKE '%compra%'")
            ->whereRaw("LOWER(COALESCE(status_name, '')) NOT LIKE '%anul%'")
            ->when($filters['client_id'] ?? null, fn (Builder $query, $id) => $query->where('client_id', $id))
            ->orderBy('issue_date')->orderBy('id');
    }

    private function operationKey(Invoice $invoice): string
    {
        if ($invoice->is_bill_without_delivery) return 'invoice_without_delivery';
        $prefix = $invoice->receipt_types_prefix;
        $name = mb_strtolower((string) $invoice->receipt_type_name);
        if (!$prefix) {
            if (str_contains($name, 'crédito') || str_contains($name, 'credito')) return 'credit_note';
            if (str_contains($name, 'débito') || str_contains($name, 'debito')) return 'debit_note';
            if (str_contains($name, 'remito')) return 'delivery_note';
            if (str_contains($name, 'pedido')) return 'customer_order';
            if (str_contains($name, 'presupuesto')) return 'budget';
            if (str_contains($name, 'factura')) return 'invoice';
        }
        return match ($prefix) {
            'FV' => 'invoice', 'NC' => 'credit_note', 'ND' => 'debit_note', 'RE' => 'delivery_note',
            'NP' => 'customer_order', 'PR' => 'budget', default => 'other',
        };
    }

    private function operationLabel(Invoice $invoice): string
    {
        if ($invoice->is_bill_without_delivery) return 'Factura sin Entrega';
        return match ($this->operationKey($invoice)) {
            'invoice' => 'Factura de Venta', 'credit_note' => 'Nota de Crédito', 'debit_note' => 'Nota de Débito',
            'delivery_note' => 'Remito de Venta', 'customer_order' => 'Nota de Pedido', 'budget' => 'Presupuesto',
            default => $invoice->receipt_type_name ?: 'Otro comprobante',
        };
    }

    private function channelLabel(Invoice $invoice): string
    {
        if ($invoice->service_channel) return $invoice->service_channel;
        if ($invoice->ecommerce_number) return 'E-commerce';
        return match ((int) $invoice->service_channel_id) { 2 => 'E-commerce', 3 => 'Aplicación móvil', default => 'ERP' };
    }

    private function paymentMethods(Invoice $invoice): string
    {
        $labels = $invoice->payments->map(fn ($payment) => match ($payment->payment_method) {
            'cash' => 'Efectivo', 'credit_card' => 'Tarjeta de crédito', 'debit_card' => 'Tarjeta de débito',
            'transfer' => 'Transferencia', 'current_account', 'checking_account' => 'Cuenta corriente',
            default => $payment->payment_method ? ucfirst(str_replace('_', ' ', $payment->payment_method)) : 'Sin informar',
        })->unique()->values();
        return $labels->isEmpty() ? 'Sin informar' : $labels->implode(' + ');
    }

    private function financing(Invoice $invoice): string
    {
        $plans = $invoice->payments->map(fn ($payment) => $payment->card_plan ?: ($payment->payment_method === 'current_account' ? ($invoice->payment_condition_name ?: 'Cuenta corriente') : null))->filter()->unique();
        return $plans->isEmpty() ? '—' : $plans->implode(' + ');
    }

    private function userIdentities(Collection $users): Collection
    {
        $map = collect();
        $users->each(function (User $user) use ($map) {
            foreach ([$user->name, $user->username, $user->email] as $identity) if ($identity) $map->put(mb_strtolower((string) $identity), $user);
        });
        return $map;
    }

    private function seller(Invoice $invoice, Collection $users, Collection $identities): ?User
    {
        if ($invoice->report_seller_user_id && $users->has((int) $invoice->report_seller_user_id)) return $users->get((int) $invoice->report_seller_user_id);
        foreach ([$invoice->user_name, $invoice->seller_full_name] as $identity) if ($identity && $identities->has(mb_strtolower($identity))) return $identities->get(mb_strtolower($identity));
        return null;
    }

    private function pointOfSale(Invoice $invoice): string
    {
        return $invoice->report_point_of_sale ?: ($invoice->arca_point_of_sale ? 'Punto '.$invoice->arca_point_of_sale : ($invoice->first_number ? 'Punto '.$invoice->first_number : 'Sin informar'));
    }

    private function pointsOfSale(): Collection
    {
        return CashSheet::query()->whereNotNull('pos_name')->where('pos_name', '!=', '')->distinct()->pluck('pos_name')
            ->merge(Invoice::query()->whereNotNull('arca_point_of_sale')->distinct()->pluck('arca_point_of_sale')->map(fn ($v) => 'Punto '.$v))
            ->merge(Invoice::query()->whereNotNull('first_number')->where('first_number', '!=', '')->distinct()->pluck('first_number')->map(fn ($v) => 'Punto '.$v))
            ->filter()->unique()->sort()->values();
    }

    private function costHistory(Collection $items): Collection
    {
        $variantIds = $items->pluck('product_variant_id')->filter()->unique();
        $productIds = $items->map(fn (InvoiceItem $item) => $item->variant?->product_id)->filter()->unique();
        $names = $items->pluck('product_name')->filter()->unique();
        if ($variantIds->isEmpty() && $productIds->isEmpty() && $names->isEmpty()) return collect();
        return PurchaseItem::query()->with('purchase:id,issue_date')->whereHas('purchase')->where(function (Builder $q) use ($variantIds, $productIds, $names) {
            if ($variantIds->isNotEmpty()) $q->whereIn('product_variant_id', $variantIds);
            if ($productIds->isNotEmpty()) ($variantIds->isNotEmpty() ? $q->orWhereIn('product_id', $productIds) : $q->whereIn('product_id', $productIds));
            if ($names->isNotEmpty()) (($variantIds->isNotEmpty() || $productIds->isNotEmpty()) ? $q->orWhereIn('product_name', $names) : $q->whereIn('product_name', $names));
        })->get();
    }

    private function invoiceCost(Invoice $invoice, Collection $history): float
    {
        return round((float) $invoice->items->sum(function (InvoiceItem $item) use ($invoice, $history) {
            $product = $item->variant?->product;
            $purchase = $history->filter(fn (PurchaseItem $candidate) => $candidate->purchase?->issue_date && $candidate->purchase->issue_date->lte($invoice->issue_date)
                && (($item->product_variant_id && (int) $candidate->product_variant_id === (int) $item->product_variant_id)
                    || ($product && (int) $candidate->product_id === (int) $product->id)
                    || ($item->product_name && mb_strtolower((string) $candidate->product_name) === mb_strtolower((string) $item->product_name))))
                ->sortByDesc(fn (PurchaseItem $candidate) => $candidate->purchase->issue_date)->first();
            $unitCost = $purchase?->unit_price;
            if ($unitCost === null && $product) $unitCost = $product->cost_with_discount ?: $product->replacement_cost ?: $product->last_purchase_price ?: 0;
            return (float) $unitCost * (float) $item->quantity;
        }), 2);
    }
}
