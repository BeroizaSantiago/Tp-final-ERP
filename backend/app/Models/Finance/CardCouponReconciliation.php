<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Tarjeta Cupón Conciliación.
 *
 * Representa la información persistida y las relaciones de Tarjeta Cupón Conciliación dentro del ERP.
 */
class CardCouponReconciliation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'datetime',
        'accreditation_date' => 'datetime',
        'gross_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'withholding_amount' => 'decimal:2',
        'other_discount_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function items() { return $this->hasMany(CardCouponReconciliationItem::class); }
    public function coupons() { return $this->belongsToMany(CardCoupon::class, 'card_coupon_reconciliation_items'); }
    public function bank() { return $this->belongsTo(Bank::class); }
    public function bankAccount() { return $this->belongsTo(BankAccount::class); }
    public function bankMovement() { return $this->belongsTo(BankMovement::class); }
    public function createdByUser() { return $this->belongsTo(User::class, 'created_by'); }
}
