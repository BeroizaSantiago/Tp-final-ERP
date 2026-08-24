<?php

namespace App\Services\Finance;

use App\Models\Finance\CashSheet;
use App\Models\Finance\CashSheetMovement;
use App\Models\Purchases\Provider;
use App\Models\Purchases\ProviderPaymentOrder;
use App\Models\Purchases\ProviderPaymentOrderApplication;
use App\Models\Purchases\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProviderPaymentOrderService
{
    public function __construct(private readonly TreasuryService $treasuryService) {}

    public function create(Provider $provider, array $data, ?int $userId): ProviderPaymentOrder
    {
        return DB::transaction(function () use ($provider, $data, $userId) {
            $amount = round((float) $data['amount'], 4);
            $purchases = Purchase::query()->where('provider_id', $provider->id)->where('balance', '>', 0)
                ->when(!empty($data['purchase_ids']), fn ($query) => $query->whereIn('id', $data['purchase_ids']))
                ->where(fn ($query) => $query->whereNull('receipt_types_prefix')->orWhereNotIn('receipt_types_prefix', ['NC']))
                ->orderBy('payment_due_date')->orderBy('issue_date')->orderBy('id')->lockForUpdate()->get();

            $pending = round((float) $purchases->sum('balance'), 4);
            if ($pending <= 0 || $amount > $pending) {
                throw ValidationException::withMessages(['amount' => $pending <= 0
                    ? 'El proveedor no tiene comprobantes pendientes seleccionados.'
                    : 'El importe supera el saldo de los comprobantes seleccionados.']);
            }

            $order = ProviderPaymentOrder::create([
                'provider_id'=>$provider->id, 'number'=>$this->nextNumber(),
                'issue_date'=>$data['payment_date'] ?? today(), 'total_amount'=>$amount,
                'payment_method'=>$data['payment_method'], 'origin'=>$data['origin'] ?? 'current_account',
                'bank_name'=>$data['bank_name'] ?? null, 'reference'=>$data['reference'] ?? null,
                'notes'=>$data['notes'] ?? null, 'created_by'=>$userId,
            ]);

            $remaining = $amount;
            foreach ($purchases as $purchase) {
                if ($remaining <= 0) break;
                $applied = min((float) $purchase->balance, $remaining);
                $payment = $purchase->payments()->create([
                    'payment_method'=>$data['payment_method'], 'amount'=>$applied,
                    'discount_amount'=>0, 'surcharge_amount'=>0, 'total_paid'=>$applied,
                    'bank_name'=>$data['bank_name'] ?? null, 'reference'=>$order->number,
                    'notes'=>$data['notes'] ?? null,
                ]);
                ProviderPaymentOrderApplication::create([
                    'provider_payment_order_id'=>$order->id, 'purchase_id'=>$purchase->id,
                    'purchase_payment_id'=>$payment->id, 'amount'=>$applied,
                ]);
                $newBalance = round((float) $purchase->balance - $applied, 4);
                $purchase->update(['balance'=>$newBalance, 'status_name'=>$newBalance <= 0 ? 'Pagada' : 'Pendiente', 'status_id'=>$newBalance <= 0 ? 2 : 1]);
                $remaining = round($remaining - $applied, 4);
            }

            $treasury = $this->resolveTreasury($userId);
            CashSheetMovement::create([
                'cash_sheet_id'=>$treasury->id, 'cash_box_id'=>$treasury->cash_box_id,
                'provider_payment_order_id'=>$order->id, 'user_id'=>$userId,
                'movement_type'=>'expense', 'payment_method'=>$order->payment_method,
                'affects_cash_balance'=>in_array(strtolower($order->payment_method), ['cash','efectivo'], true),
                'amount'=>$amount, 'document_number'=>$order->number, 'customer_name'=>$provider->name,
                'reference'=>$order->reference, 'description'=>'Orden de pago '.$order->number, 'status'=>'active',
            ]);

            return $order->load(['provider','applications.purchase','movements','createdBy']);
        });
    }

    public function cancel(ProviderPaymentOrder $order, string $reason, ?int $userId): ProviderPaymentOrder
    {
        return DB::transaction(function () use ($order, $reason, $userId) {
            $order = ProviderPaymentOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($order->status === 'cancelled') throw ValidationException::withMessages(['order'=>'La orden de pago ya está anulada.']);
            $order->load(['applications.purchasePayment','applications.purchase','movements']);

            foreach ($order->applications as $application) {
                $purchase = Purchase::query()->lockForUpdate()->find($application->purchase_id);
                if ($purchase) {
                    $balance = min((float) $purchase->total_amount, (float) $purchase->balance + (float) $application->amount);
                    $purchase->update(['balance'=>$balance, 'status_name'=>'Pendiente', 'status_id'=>1]);
                }
                $application->purchasePayment?->delete();
            }

            foreach ($order->movements->whereNull('reversal_of_movement_id')->where('status', 'active') as $movement) {
                CashSheetMovement::create([
                    'cash_sheet_id'=>$movement->cash_sheet_id, 'cash_box_id'=>$movement->cash_box_id,
                    'provider_payment_order_id'=>$order->id, 'reversal_of_movement_id'=>$movement->id,
                    'user_id'=>$userId, 'movement_type'=>'income', 'payment_method'=>$movement->payment_method,
                    'affects_cash_balance'=>$movement->affects_cash_balance, 'amount'=>$movement->amount,
                    'document_number'=>$order->number, 'customer_name'=>$order->provider?->name,
                    'reference'=>'ANULACIÓN', 'description'=>'Reversión de '.$order->number.': '.$reason, 'status'=>'active',
                ]);
            }

            $order->update(['status'=>'cancelled','cancelled_at'=>now(),'cancelled_by'=>$userId,'cancellation_reason'=>$reason]);
            return $order->fresh()->load(['provider','applications.purchase','movements','createdBy','cancelledBy']);
        });
    }

    private function resolveTreasury(?int $userId): CashSheet
    {
        $salesSheet = $userId ? CashSheet::query()->where('user_id',$userId)->where('status_name','Abierta')->whereNull('closing_date')
            ->whereHas('cashBox', fn ($q) => $q->where(fn ($t) => $t->whereNull('box_type_name')->orWhere('box_type_name','!=','TESORERIA')))
            ->with('cashBox')->latest('opening_date')->first() : null;
        if ($salesSheet?->cashBox && ($treasury = $this->treasuryService->openTreasuryFor($salesSheet->cashBox))) return $treasury;
        $treasuries = CashSheet::query()->where('status_name','Abierta')->whereNull('closing_date')
            ->whereHas('cashBox', fn ($q) => $q->where('box_type_name','TESORERIA'))->limit(2)->lockForUpdate()->get();
        if ($treasuries->count() === 1) return $treasuries->first();
        throw ValidationException::withMessages(['treasury'=>$treasuries->isEmpty() ? 'La Tesorería debe estar abierta para emitir la orden de pago.' : 'No se pudo determinar la Tesorería de la sucursal.']);
    }

    private function nextNumber(): string
    {
        $next = (ProviderPaymentOrder::query()->lockForUpdate()->max('id') ?? 0) + 1;
        return 'OP-'.now()->format('Y').'-'.str_pad((string) $next, 8, '0', STR_PAD_LEFT);
    }
}
