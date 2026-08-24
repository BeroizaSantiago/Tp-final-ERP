<?php

namespace App\Services\Reports\Suppliers;

use App\Models\Purchases\Provider;
use Illuminate\Support\Collection;

/** Separa la deuda pendiente de proveedores entre vencida y futura. */
class SupplierCreditorsReportService
{
    public function generate(array $filters): array
    {
        $today = today();
        $providers = Provider::query()->with([
            'purchases' => fn ($query) => $query->where('balance', '>', 0)
                ->where(fn ($nested) => $nested->whereNull('receipt_types_prefix')->orWhereNotIn('receipt_types_prefix', ['NC'])),
            'paymentOrders' => fn ($query) => $query->where('status', '!=', 'cancelled')->latest('issue_date'),
        ])->when($filters['provider_ids'] ?? null, fn ($query, $ids) => $query->whereIn('id', $ids))->get();

        $rows = $providers->map(function ($provider) use ($today) {
            $expired = $provider->purchases
                ->filter(fn ($purchase) => $purchase->payment_due_date
                    && $purchase->payment_due_date->copy()->startOfDay()->lt($today))
                ->sum('balance');
            $balance = $provider->purchases->sum('balance');
            $lastPayment = $provider->paymentOrders->first();

            return [
                'provider' => $provider->name,
                'currency' => $provider->purchases->first()?->currency_name ?: 'Pesos',
                'balance' => round((float) $balance, 2),
                'expired' => round((float) $expired, 2),
                'future' => round((float) ($balance - $expired), 2),
                'last_payment_date' => $lastPayment?->issue_date?->format('d/m/Y') ?: '-',
                'last_payment_amount' => (float) ($lastPayment?->total_amount ?? 0),
                'email' => 'Sin informar',
                'phone' => $provider->primary_phone ?: '-',
            ];
        })->filter(fn ($row) => match ($filters['debt_type']) {
            'expired' => $row['expired'] > 0,
            'future' => $row['future'] > 0,
            default => $row['balance'] > 0,
        })->values();

        return [
            'filters' => $filters,
            'rows' => $rows->all(),
            'summary' => [
                'balance' => round((float) $rows->sum('balance'), 2),
                'expired' => round((float) $rows->sum('expired'), 2),
                'future' => round((float) $rows->sum('future'), 2),
            ],
        ];
    }

    public function options(): array
    {
        return ['providers' => Provider::query()->orderBy('name')->get(['id', 'name', 'identification_number'])];
    }

    public function exportRows(array $report): Collection
    {
        return collect($report['rows'])->map(fn ($row) => array_values($row));
    }
}
