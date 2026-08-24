<?php

namespace App\Services\Payments;

use App\Models\Clients\CustomerReceipt;
use App\Models\Clients\CustomerReceiptApplication;
use App\Models\Clients\CustomerReceiptPayment;
use App\Models\Finance\CashSheetMovement;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoicePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class InvoicePaymentSettlementService
{
    public function synchronizeMercadoPago(
        InvoicePayment $payment,
        array $remoteOrder,
    ): InvoicePayment {
        return DB::transaction(function () use ($payment, $remoteOrder) {
            $payment = InvoicePayment::query()
                ->with(['invoice.client', 'cashSheet.cashBox', 'treasuryCashSheet.cashBox'])
                ->lockForUpdate()
                ->findOrFail($payment->id);

            $invoice = Invoice::query()->lockForUpdate()->findOrFail($payment->invoice_id);

            $this->validateRemoteOrder($payment, $remoteOrder);

            $remoteStatus = (string) ($remoteOrder['status'] ?? '');
            $statusDetail = (string) ($remoteOrder['status_detail'] ?? '');
            $providerPaymentId = data_get($remoteOrder, 'transactions.payments.0.id');

            $updates = [
                'provider_payment_id' => $providerPaymentId ?: $payment->provider_payment_id,
                'provider_status_detail' => $statusDetail ?: null,
                'provider_payload' => $remoteOrder,
            ];

            if (in_array($remoteStatus, ['canceled', 'expired', 'refunded'], true)) {
                $updates['status'] = match ($remoteStatus) {
                    'canceled' => 'cancelled',
                    default => $remoteStatus,
                };
                $payment->update($updates);

                return $payment->fresh();
            }

            if ($remoteStatus !== 'processed') {
                $updates['status'] = 'pending';
                $payment->update($updates);

                return $payment->fresh();
            }

            if ($payment->status === 'approved') {
                return $payment;
            }

            $updates['status'] = 'approved';
            $updates['total_paid'] = $payment->amount;
            $updates['approved_at'] = now();
            $payment->update($updates);

            if (! $payment->cashSheet || ! $payment->treasuryCashSheet) {
                throw new RuntimeException('El pago QR no tiene asociadas la caja de venta y la Tesorería.');
            }

            CashSheetMovement::firstOrCreate(
                [
                    'invoice_payment_id' => $payment->id,
                    'cash_sheet_id' => $payment->cash_sheet_id,
                ],
                [
                    'cash_box_id' => $payment->cashSheet->cash_box_id,
                    'invoice_id' => $invoice->id,
                    'user_id' => $payment->created_by,
                    'movement_type' => 'informative',
                    'payment_method' => 'mercado_pago_qr',
                    'affects_cash_balance' => false,
                    'amount' => $payment->amount,
                    'document_number' => $invoice->full_number ?? 'Venta #'.$invoice->id,
                    'customer_name' => $invoice->customer_name,
                    'reference' => $payment->provider_order_id,
                    'description' => 'Cobro Mercado Pago QR de venta '.($invoice->full_number ?? '#'.$invoice->id),
                    'status' => 'active',
                ],
            );

            CashSheetMovement::firstOrCreate(
                [
                    'invoice_payment_id' => $payment->id,
                    'cash_sheet_id' => $payment->treasury_cash_sheet_id,
                ],
                [
                    'cash_box_id' => $payment->treasuryCashSheet->cash_box_id,
                    'origin_cash_box_id' => $payment->cashSheet->cash_box_id,
                    'invoice_id' => $invoice->id,
                    'user_id' => $payment->created_by,
                    'movement_type' => 'income',
                    'payment_method' => 'mercado_pago_qr',
                    'affects_cash_balance' => false,
                    'amount' => $payment->amount,
                    'document_number' => $invoice->full_number ?? 'Venta #'.$invoice->id,
                    'customer_name' => $invoice->customer_name,
                    'reference' => $payment->provider_order_id,
                    'description' => 'Acreditación inmediata de Mercado Pago QR '.($invoice->full_number ?? '#'.$invoice->id),
                    'status' => 'active',
                ],
            );

            $totalCollected = (float) $invoice->payments()
                ->where('status', 'approved')
                ->sum('total_paid');
            $balance = max(0, (float) $invoice->total_amount - $totalCollected);

            $invoice->update([
                'balance' => $balance,
                'status_name' => $balance <= 0.005 ? 'Cobrada' : 'Pend. Cobro',
                'status_id' => $balance <= 0.005 ? 2 : 6,
            ]);

            if ($invoice->client_id) {
                $receipt = CustomerReceipt::create([
                    'client_id' => $invoice->client_id,
                    'number' => 'RC-'.now()->format('Y').'-'.str_pad(
                        (string) ((CustomerReceipt::max('id') ?? 0) + 1),
                        8,
                        '0',
                        STR_PAD_LEFT,
                    ),
                    'receipt_date' => today(),
                    'total_amount' => $payment->amount,
                    'discount_amount' => 0,
                    'surcharge_amount' => 0,
                    'net_amount' => $payment->amount,
                    'notes' => 'Mercado Pago QR '.$payment->provider_order_id,
                ]);

                CustomerReceiptPayment::create([
                    'customer_receipt_id' => $receipt->id,
                    'payment_method' => 'mercado_pago_qr',
                    'amount' => $payment->amount,
                    'reference' => $payment->provider_order_id,
                    'notes' => 'Confirmado automáticamente por Mercado Pago.',
                ]);

                CustomerReceiptApplication::create([
                    'customer_receipt_id' => $receipt->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $payment->amount,
                ]);

                $invoice->client()->update(['last_payment_date' => now()]);
            }

            return $payment->fresh();
        });
    }

    private function validateRemoteOrder(InvoicePayment $payment, array $order): void
    {
        $orderId = (string) ($order['id'] ?? '');
        $reference = (string) ($order['external_reference'] ?? '');
        $amount = (float) ($order['total_amount'] ?? 0);
        $currency = (string) ($order['currency'] ?? 'ARS');
        $externalPosId = (string) data_get($order, 'config.qr.external_pos_id', '');
        $configuredPosId = (string) config('services.mercadopago.external_pos_id');
        $remoteUserId = (string) ($order['user_id'] ?? '');
        $configuredUserId = (string) config('services.mercadopago.user_id');

        $valid = $orderId !== ''
            && hash_equals((string) $payment->provider_order_id, $orderId)
            && hash_equals((string) $payment->external_reference, $reference)
            && abs((float) $payment->amount - $amount) <= 0.01
            && $currency === 'ARS'
            && ($configuredPosId === '' || hash_equals($configuredPosId, $externalPosId))
            && ($configuredUserId === '' || $remoteUserId === '' || hash_equals($configuredUserId, $remoteUserId));

        if (! $valid) {
            Log::warning('Se rechazó una confirmación QR por datos inconsistentes.', [
                'invoice_payment_id' => $payment->id,
                'provider_order_id' => $orderId,
                'external_reference' => $reference,
                'amount' => $amount,
                'currency' => $currency,
                'external_pos_id' => $externalPosId,
            ]);

            throw new RuntimeException('La orden informada por Mercado Pago no coincide con el pago local.');
        }
    }
}
