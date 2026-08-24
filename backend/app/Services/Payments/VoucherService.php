<?php

namespace App\Services\Payments;

use App\Models\Sales\Invoice;
use App\Models\Sales\InvoicePayment;
use App\Models\Sales\Voucher;
use App\Models\Clients\CustomerReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoucherService
{
    public const RESERVATION_MINUTES = 30;

    public function reserve(string $code, Invoice $invoice): Voucher
    {
        $voucher = Voucher::query()->where('code', trim($code))->lockForUpdate()->first();
        if (! $voucher) $this->fail('El voucher no existe.');
        if ($this->reservationExpired($voucher)) {
            $this->releaseLocked($voucher);
            $voucher->refresh();
        }
        if ($voucher->isExpired()) $this->fail('El voucher está vencido.');
        if ($voucher->status === 'used') $this->fail('El voucher ya fue utilizado.');
        if ($voucher->status === 'disabled') $this->fail('El voucher está inhabilitado.');
        if ($voucher->status === 'reserved' && (int) $voucher->reserved_invoice_id !== (int) $invoice->id) {
            $this->fail('El voucher está reservado en otra venta.');
        }

        $voucher->update([
            'status' => 'reserved',
            'reserved_invoice_id' => $invoice->id,
            'reserved_at' => now(),
        ]);

        return $voucher->fresh();
    }

    public function release(Voucher $voucher, ?Invoice $invoice = null): Voucher
    {
        return DB::transaction(function () use ($voucher, $invoice) {
            $voucher = Voucher::query()->lockForUpdate()->findOrFail($voucher->id);
            if ($voucher->status !== 'reserved') return $voucher;
            if ($invoice && (int) $voucher->reserved_invoice_id !== (int) $invoice->id) {
                $this->fail('El voucher está reservado en otra venta.');
            }

            return $this->releaseLocked($voucher);
        });
    }

    public function releaseIfExpired(Voucher $voucher): Voucher
    {
        if (! $this->reservationExpired($voucher)) return $voucher;
        return $this->release($voucher);
    }

    public function releaseExpiredReservations(int $limit = 100): void
    {
        Voucher::query()
            ->where('status', 'reserved')
            ->where('reserved_at', '<=', now()->subMinutes(self::RESERVATION_MINUTES))
            ->limit($limit)
            ->pluck('id')
            ->each(function ($id) {
                $voucher = Voucher::query()->find($id);
                if ($voucher) $this->releaseIfExpired($voucher);
            });
    }

    public function consume(Voucher $voucher, ?Invoice $invoice = null): Voucher
    {
        $voucher = Voucher::query()->lockForUpdate()->findOrFail($voucher->id);
        if ($voucher->status === 'used') {
            if (! $invoice || (int) $voucher->used_invoice_id === (int) $invoice->id) return $voucher;
            $this->fail('El voucher ya fue utilizado.');
        }
        if ($voucher->isExpired()) $this->fail('El voucher está vencido.');
        if ($voucher->status === 'disabled') $this->fail('El voucher está inhabilitado.');
        if ($invoice && $voucher->reserved_invoice_id && (int) $voucher->reserved_invoice_id !== (int) $invoice->id) {
            $this->fail('El voucher está reservado en otra venta.');
        }

        $voucher->update([
            'status' => 'used',
            'used_at' => now(),
            'used_invoice_id' => $invoice?->id,
            'reserved_invoice_id' => null,
            'reserved_at' => null,
        ]);
        return $voucher->fresh();
    }

    private function reservationExpired(Voucher $voucher): bool
    {
        return $voucher->status === 'reserved'
            && $voucher->reserved_at
            && $voucher->reserved_at->lte(now()->subMinutes(self::RESERVATION_MINUTES));
    }

    private function releaseLocked(Voucher $voucher): Voucher
    {
        $invoiceId = $voucher->reserved_invoice_id;
        $payments = InvoicePayment::query()
            ->where('voucher_id', $voucher->id)
            ->when($invoiceId, fn ($query) => $query->where('invoice_id', $invoiceId))
            ->get();

        foreach ($payments as $payment) {
            $movements = $payment->cashMovements()->get();
            $receiptIds = $movements->pluck('customer_receipt_id')->filter()->unique();
            $payment->cashMovements()->delete();

            foreach ($receiptIds as $receiptId) {
                $receipt = CustomerReceipt::query()->find($receiptId);
                if (! $receipt) continue;
                $receipt->applications()->delete();
                $receipt->payments()->delete();
                $receipt->delete();
            }

            $payment->delete();
        }

        $voucher->update([
            'status' => 'available',
            'reserved_invoice_id' => null,
            'reserved_at' => null,
        ]);

        if ($invoiceId && ($invoice = Invoice::query()->find($invoiceId)) && ! $invoice->stock_applied_at) {
            $collected = (float) $invoice->payments()->sum('total_paid');
            $invoice->update([
                'balance' => max(0, (float) $invoice->total_amount - $collected),
                'status_name' => 'Pend. Cobro',
                'status_id' => 6,
            ]);
        }

        return $voucher->fresh();
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['voucher_code' => $message]);
    }
}
