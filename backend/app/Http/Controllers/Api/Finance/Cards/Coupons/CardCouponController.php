<?php

namespace App\Http\Controllers\Api\Finance\Cards\Coupons;

use App\Http\Controllers\Controller;
use App\Models\Finance\CardCoupon;
use Illuminate\Http\Request;

/**
 * Gestiona cupones de tarjetas de credito.
 *
 * Registra cupones con datos de tarjeta, lote, cuotas, importes, comisiones,
 * fechas esperadas y referencias comerciales para su seguimiento.
 */
class CardCouponController extends Controller
{
    public function index()
    {
        return CardCoupon::latest('creation_date')->paginate(20);
    }

    public function show(CardCoupon $cardCoupon)
    {
        return $cardCoupon;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['nullable'],
            'credit_card' => ['nullable', 'string'],
            'last_digits_card' => ['nullable', 'string'],
            'credit_card_id' => ['nullable'],
            'credit_card_plan' => ['nullable', 'string'],
            'credit_card_plan_id' => ['nullable'],
            'lot_number' => ['nullable', 'string'],
            'coupon_number' => ['required', 'string'],
            'instalments' => ['nullable', 'integer'],
            'coupon_status' => ['nullable', 'string'],
            'status_id' => ['nullable'],
            'coupon_amount' => ['required', 'numeric'],
            'commission' => ['nullable', 'numeric'],
            'charge_amount' => ['nullable', 'numeric'],
            'creation_date' => ['nullable', 'date'],
            'expected_date' => ['nullable', 'date'],
            'credit_card_type' => ['nullable', 'string'],
            'receipt_number' => ['nullable', 'string'],
            'customer_name' => ['nullable', 'string'],
            'trade_number' => ['nullable', 'string'],
            'currency_id' => ['nullable'],
        ]);

        return CardCoupon::create($data);
    }

    public function update(Request $request, CardCoupon $cardCoupon)
{
    $data = $request->validate([
        'coupon_status' => ['nullable', 'string'],
        'commission' => ['nullable', 'numeric'],
        'charge_amount' => ['nullable', 'numeric'],
        'expected_date' => ['nullable', 'date'],
    ]);

    $cardCoupon->update($data);

    return $cardCoupon->fresh();
}
}
