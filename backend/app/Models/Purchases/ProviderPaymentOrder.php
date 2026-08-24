<?php

namespace App\Models\Purchases;

use App\Models\Finance\CashSheetMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Proveedor Pago Orden.
 *
 * Representa la información persistida y las relaciones de Proveedor Pago Orden dentro del ERP.
 */
class ProviderPaymentOrder extends Model
{
    protected $guarded = [];

    protected $casts = ['issue_date'=>'date', 'total_amount'=>'decimal:4', 'cancelled_at'=>'datetime'];

    public function provider() { return $this->belongsTo(Provider::class); }
    public function applications() { return $this->hasMany(ProviderPaymentOrderApplication::class); }
    public function movements() { return $this->hasMany(CashSheetMovement::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function cancelledBy() { return $this->belongsTo(User::class, 'cancelled_by'); }
}
