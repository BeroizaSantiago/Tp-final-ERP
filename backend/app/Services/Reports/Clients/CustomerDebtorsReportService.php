<?php

namespace App\Services\Reports\Clients;

use App\Models\Clients\Client;
use Illuminate\Support\Collection;

/** Resume saldos vencidos y futuros de la cuenta corriente de clientes. */
class CustomerDebtorsReportService
{
    public function generate(array $f): array
    {
        $today = today();
        $clients = Client::query()->with(['invoices' => fn($q) => $q->where('balance', '>', 0)->where(fn($x) => $x->where('payment_condition_name', 'Cuenta Corriente')->orWhereHas('payments', fn($p) => $p->where('payment_method', 'current_account')))->where(fn($x) => $x->whereIn('receipt_types_prefix', ['FV', 'ND'])->orWhereNull('receipt_types_prefix')), 'receipts' => fn($q) => $q->where('status', '!=', 'cancelled')->latest('receipt_date')])->when($f['client_ids'] ?? null, fn($q, $v) => $q->whereIn('id', $v))->when($f['sellers'] ?? null, fn($q, $v) => $q->whereIn('seller', $v))->when($f['provinces'] ?? null, fn($q, $v) => $q->whereIn('state', $v))->get();
        $rows = $clients->map(function ($c) use ($today) {
            $expired = $c->invoices->filter(fn($i) => $i->payment_due_date && $i->payment_due_date->startOfDay()->lt($today))->sum('balance');
            $future = $c->invoices->sum('balance') - $expired;
            $last = $c->receipts->first();
            return ['client' => $c->name, 'currency' => $c->currency ?: 'Pesos', 'balance' => round((float)$c->invoices->sum('balance'), 2), 'expired' => round((float)$expired, 2), 'future' => round((float)$future, 2), 'last_payment_date' => $last?->receipt_date?->format('d/m/Y') ?: '—', 'last_payment_amount' => (float)($last?->total_amount ?? 0), 'seller' => $c->seller ?: 'Sin vendedor', 'email' => $c->email ?: '—', 'phone' => $c->first_phone ?: $c->second_phone ?: '—'];
        })->filter(fn($r) => match ($f['debt_type']) {
            'expired' => $r['expired'] > 0,
            'future' => $r['future'] > 0,
            default => $r['balance'] > 0
        })->values();
        return ['filters' => $f, 'rows' => $rows->all(), 'summary' => ['balance' => round((float)$rows->sum('balance'), 2), 'expired' => round((float)$rows->sum('expired'), 2), 'future' => round((float)$rows->sum('future'), 2)]];
    }
    public function options(): array
    {
        return ['clients' => Client::query()->orderBy('name')->get(['id', 'name', 'document_number']), 'sellers' => Client::query()->whereNotNull('seller')->where('seller', '!=', '')->distinct()->orderBy('seller')->pluck('seller'), 'provinces' => Client::query()->whereNotNull('state')->where('state', '!=', '')->distinct()->orderBy('state')->pluck('state')];
    }
    public function exportRows(array $r): Collection
    {
        return collect($r['rows'])->map(fn($x) => array_values($x));
    }
}
