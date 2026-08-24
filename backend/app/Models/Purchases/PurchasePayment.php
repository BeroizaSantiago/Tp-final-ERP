<?php

namespace App\Models\Purchases;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Compra Pago.
 *
 * Representa la información persistida y las relaciones de Compra Pago dentro del ERP.
 */
class PurchasePayment extends Model
{
    protected $guarded = [];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
