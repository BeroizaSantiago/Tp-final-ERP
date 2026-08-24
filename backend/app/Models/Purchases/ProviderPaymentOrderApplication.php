<?php

namespace App\Models\Purchases;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Proveedor Pago Orden Aplicación.
 *
 * Representa la información persistida y las relaciones de Proveedor Pago Orden Aplicación dentro del ERP.
 */
class ProviderPaymentOrderApplication extends Model
{
    protected $guarded = [];
    protected $casts = ['amount'=>'decimal:4'];

    public function paymentOrder() { return $this->belongsTo(ProviderPaymentOrder::class, 'provider_payment_order_id'); }
    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function purchasePayment() { return $this->belongsTo(PurchasePayment::class); }
}
