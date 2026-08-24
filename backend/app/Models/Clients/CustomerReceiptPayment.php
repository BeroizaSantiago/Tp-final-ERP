<?php

namespace App\Models\Clients;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Cliente Recibo Pago.
 *
 * Representa la información persistida y las relaciones de Cliente Recibo Pago dentro del ERP.
 */
class CustomerReceiptPayment extends Model
{
    protected $fillable = [
        'customer_receipt_id',
        'payment_method',
        'amount',
        'card_name',
        'card_plan',
        'bank_name',
        'reference',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function receipt()
    {
        return $this->belongsTo(CustomerReceipt::class,'customer_receipt_id');
    }

    public function cashSheetMovement()
    {
        return $this->hasOne(\App\Models\Finance\CashSheetMovement::class);
    }
}
