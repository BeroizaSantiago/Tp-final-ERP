<?php

namespace App\Http\Controllers\Api\Finance\Cards\Coupons;

use App\Http\Controllers\Controller;
use App\Models\Finance\BankAccount;
use App\Models\Finance\Bank;
use App\Models\Finance\CardCoupon;
use App\Models\Finance\CardCouponReconciliation;
use App\Services\Finance\BankMovementService;
use App\Models\Sales\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * Controlador de Tarjeta Cupón Conciliación.
 *
 * Coordina las solicitudes, validaciones y respuestas del módulo Tarjeta Cupón Conciliación del ERP.
 */
class CardCouponReconciliationController extends Controller
{
    public function __construct(private readonly BankMovementService $bankMovementService) {}

    public function index()
    {
        return CardCouponReconciliation::with(['bank', 'bankAccount', 'bankMovement', 'createdByUser'])
            ->withCount('items')
            ->latest('issue_date')
            ->paginate(20);
    }

    public function pendingCoupons()
    {
        return CardCoupon::query()
            ->where('coupon_status', 'Pendiente')
            ->whereDoesntHave('reconciliationItem')
            ->orderBy('expected_date')
            ->orderBy('id')
            ->get();
    }

    public function options()
    {
        return response()->json([
            'banks' => Bank::query()->where('is_active', true)->orderBy('name')->get(),
            'accounts' => BankAccount::with('bank')->where('is_active', true)->orderBy('bank_name')->orderBy('account_number')->get(),
        ]);
    }

    public function show(CardCouponReconciliation $cardCouponReconciliation)
    {
        return $cardCouponReconciliation->load([
            'bank', 'bankAccount', 'bankMovement', 'createdByUser', 'items.coupon',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'coupon_ids' => ['required', 'array', 'min:1'],
            'coupon_ids.*' => ['integer', 'distinct', 'exists:card_coupons,id'],
            'settlement_number' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['required', 'date'],
            'accreditation_date' => ['required', 'date'],
            'bank_id' => ['required', 'exists:banks,id'],
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'withholding_amount' => ['nullable', 'numeric', 'min:0'],
            'withholding_description' => ['nullable', 'string', 'max:255'],
            'other_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'other_discount_description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        return DB::transaction(function () use ($data) {
            $account = BankAccount::with('bank')->lockForUpdate()->findOrFail($data['bank_account_id']);

            if ((int) $account->bank_id !== (int) $data['bank_id']) {
                throw ValidationException::withMessages(['bank_account_id' => 'La cuenta no pertenece al banco seleccionado.']);
            }

            $coupons = CardCoupon::query()->lockForUpdate()->whereIn('id', $data['coupon_ids'])->get();
            if ($coupons->count() !== count($data['coupon_ids']) || $coupons->contains(fn ($coupon) => $coupon->coupon_status !== 'Pendiente' || $coupon->reconciliationItem()->exists())) {
                throw ValidationException::withMessages(['coupon_ids' => 'Uno o más cupones ya no están pendientes. Actualizá el listado.']);
            }

            $gross = round((float) $coupons->sum('coupon_amount'), 4);
            $commissions = round((float) $coupons->sum('commission'), 4);
            $withholdings = round((float) ($data['withholding_amount'] ?? 0), 4);
            $otherDiscounts = round((float) ($data['other_discount_amount'] ?? 0), 4);
            $net = round($gross - $commissions - $withholdings - $otherDiscounts, 4);

            if ($net <= 0) {
                throw ValidationException::withMessages(['withholding_amount' => 'El importe neto de la liquidación debe ser mayor a cero.']);
            }

            $number = 'CT-'.now()->format('Ymd-His').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            $reconciliation = CardCouponReconciliation::create([
                'number' => $number,
                'settlement_number' => $data['settlement_number'] ?? null,
                'issue_date' => $this->issueDateWithTime($data['issue_date']),
                'accreditation_date' => $data['accreditation_date'],
                'bank_id' => $data['bank_id'],
                'bank_account_id' => $data['bank_account_id'],
                'gross_amount' => $gross,
                'commission_amount' => $commissions,
                'withholding_amount' => $withholdings,
                'withholding_description' => $data['withholding_description'] ?? null,
                'other_discount_amount' => $otherDiscounts,
                'other_discount_description' => $data['other_discount_description'] ?? null,
                'net_amount' => $net,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
                'status' => 'Confirmada',
            ]);

            foreach ($coupons as $coupon) {
                $reconciliation->items()->create([
                    'card_coupon_id' => $coupon->id,
                    'coupon_amount' => $coupon->coupon_amount,
                    'commission_amount' => $coupon->commission,
                    'net_amount' => (float) $coupon->coupon_amount - (float) $coupon->commission,
                ]);
            }

            $movement = $this->bankMovementService->create([
                'bank_account_id' => $account->id,
                'issue_date' => $data['accreditation_date'],
                'bank_concept_name' => 'Acreditación de cupones de tarjeta',
                'amount' => $net,
                'reconciled' => true,
                'council_date' => $data['accreditation_date'],
                'movement_type_name' => 'Ingreso',
                'service_channel_name' => 'Conciliación de tarjetas',
                'legend' => 'Liquidación '.$number.(!empty($data['settlement_number']) ? ' - '.$data['settlement_number'] : ''),
                'is_automatic' => true,
                'voucher' => $number,
                'mode' => 'card_coupon_reconciliation',
            ]);

            $reconciliation->update(['bank_movement_id' => $movement->id]);
            CardCoupon::whereIn('id', $coupons->pluck('id'))->update(['coupon_status' => 'Conciliado']);

            $invoiceIds = $coupons->pluck('invoice_id')->filter()->unique();
            if ($invoiceIds->isNotEmpty()) {
                Invoice::query()->whereIn('id', $invoiceIds)->lockForUpdate()->get()->each(function (Invoice $invoice) {
                    $hasPendingCreditCoupons = $invoice->cardCoupons()
                        ->whereIn('credit_card_type', ['Crédito', 'Crédito', 'Credito'])
                        ->where('coupon_status', '!=', 'Conciliado')
                        ->exists();

                    if ((float) $invoice->balance <= 0 && ! $hasPendingCreditCoupons) {
                        $invoice->update(['status_name' => 'Cobrada', 'status_id' => 2]);
                    }
                });
            }

            return response()->json([
                'message' => 'Conciliación registrada correctamente.',
                'reconciliation' => $reconciliation->fresh()->load(['items.coupon', 'bank', 'bankAccount', 'bankMovement']),
            ], 201);
        });
    }

    private function issueDateWithTime(string $value): Carbon
    {
        $date = Carbon::parse($value, config('app.timezone'));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value))) {
            $time = now();
            $date->setTime($time->hour, $time->minute, $time->second);
        }

        return $date;
    }
}
