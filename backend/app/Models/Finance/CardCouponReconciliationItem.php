<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Tarjeta Cupón Conciliación Ítem.
 *
 * Representa la información persistida y las relaciones de Tarjeta Cupón Conciliación Ítem dentro del ERP.
 */
class CardCouponReconciliationItem extends Model
{
    protected $guarded = [];
    protected $casts = ['coupon_amount' => 'decimal:2', 'commission_amount' => 'decimal:2', 'net_amount' => 'decimal:2'];
    public function reconciliation() { return $this->belongsTo(CardCouponReconciliation::class, 'card_coupon_reconciliation_id'); }
    public function coupon() { return $this->belongsTo(CardCoupon::class, 'card_coupon_id'); }
}
