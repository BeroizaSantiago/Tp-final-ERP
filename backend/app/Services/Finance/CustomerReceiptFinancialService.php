<?php

namespace App\Services\Finance;

use App\Models\Clients\CustomerReceiptPayment;
use App\Models\Finance\CashSheet;
use App\Models\Finance\CashSheetMovement;
use Illuminate\Validation\ValidationException;

class CustomerReceiptFinancialService
{
    public function __construct(private readonly TreasuryService $treasuryService)
    {
    }

    public function register(CustomerReceiptPayment $payment, ?int $userId): CashSheetMovement
    {
        $payment->loadMissing('receipt.client');
        $method = strtolower(trim((string) $payment->payment_method));
        $cashSheet = $this->openSalesSheet($userId);

        if (in_array($method, ['cash', 'efectivo'], true)) {
            if (!$cashSheet) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Debe tener una planilla de caja abierta para registrar un cobro en efectivo.',
                ]);
            }

            $destinationSheet = $cashSheet;
            $affectsCashBalance = true;
        } else {
            $destinationSheet = $this->resolveTreasurySheet($cashSheet);
            $affectsCashBalance = false;
        }

        $receipt = $payment->receipt;

        return CashSheetMovement::create([
            'cash_sheet_id' => $destinationSheet->id,
            'cash_box_id' => $destinationSheet->cash_box_id,
            'customer_receipt_id' => $receipt->id,
            'customer_receipt_payment_id' => $payment->id,
            'user_id' => $userId,
            'movement_type' => 'income',
            'payment_method' => $payment->payment_method,
            'affects_cash_balance' => $affectsCashBalance,
            'amount' => $payment->amount,
            'document_number' => $receipt->number,
            'customer_name' => $receipt->client?->name,
            'reference' => $payment->reference,
            'description' => 'Cobro de cuenta corriente '.$receipt->number,
            'status' => 'active',
        ]);
    }

    private function openSalesSheet(?int $userId): ?CashSheet
    {
        if (!$userId) {
            return null;
        }

        return CashSheet::query()
            ->where('user_id', $userId)
            ->where('status_name', 'Abierta')
            ->whereNull('closing_date')
            ->whereHas('cashBox', fn ($query) => $query
                ->where(fn ($type) => $type
                    ->whereNull('box_type_name')
                    ->orWhere('box_type_name', '!=', 'TESORERIA')))
            ->with('cashBox')
            ->lockForUpdate()
            ->latest('opening_date')
            ->first();
    }

    private function resolveTreasurySheet(?CashSheet $salesSheet): CashSheet
    {
        if ($salesSheet?->cashBox) {
            $treasurySheet = $this->treasuryService->openTreasuryFor($salesSheet->cashBox);
            if ($treasurySheet) {
                return $treasurySheet;
            }
        }

        $openTreasuries = CashSheet::query()
            ->where('status_name', 'Abierta')
            ->whereNull('closing_date')
            ->whereHas('cashBox', fn ($query) => $query->where('box_type_name', 'TESORERIA'))
            ->with('cashBox')
            ->lockForUpdate()
            ->limit(2)
            ->get();

        if ($openTreasuries->count() === 1) {
            return $openTreasuries->first();
        }

        $message = $openTreasuries->isEmpty()
            ? 'La Tesorería debe estar abierta para registrar este cobro.'
            : 'No se pudo determinar la Tesorería de la sucursal para registrar este cobro.';

        throw ValidationException::withMessages(['treasury' => $message]);
    }
}
