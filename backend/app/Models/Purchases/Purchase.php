<?php

namespace App\Models\Purchases;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Compra.
 *
 * Representa la información persistida y las relaciones de Compra dentro del ERP.
 */
class Purchase extends Model
{
    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'date',
        'payment_due_date' => 'date',
        'vat_imputation_date' => 'date',
        'total_amount' => 'decimal:4',
        'balance' => 'decimal:4',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class);
    }
}
