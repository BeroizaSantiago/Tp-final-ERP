<?php

namespace App\Models\Clients;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Cliente Recibo.
 *
 * Representa la información persistida y las relaciones de Cliente Recibo dentro del ERP.
 */
class CustomerReceipt extends Model
{
    protected $fillable = [
        'client_id',
        'number',
        'receipt_date',
        'total_amount',
        'discount_amount',
        'surcharge_amount',
        'net_amount',
        'notes',
        'status',
        'origin',
        'created_by',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'surcharge_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function payments()
    {
        return $this->hasMany(CustomerReceiptPayment::class);
    }

    public function applications()
    {
        return $this->hasMany(CustomerReceiptApplication::class);
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === 'cancelled';
    }
}
