<?php

namespace App\Models\Finance;

use App\Models\Sales\Invoice;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Tarjeta Cupón.
 *
 * Representa la información persistida y las relaciones de Tarjeta Cupón dentro del ERP.
 */
class CardCoupon extends Model
{
    protected $guarded = [];

    public function reconciliationItem()
    {
        return $this->hasOne(CardCouponReconciliationItem::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
