<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Cambio Ticket.
 *
 * Representa la información persistida y las relaciones de Cambio Ticket dentro del ERP.
 */
class ExchangeTicket extends Model
{
    protected $guarded = [];

    protected $casts = [
        'used' => 'boolean',
        'expiration_date' => 'date',
        'used_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function usedInvoice()
    {
        return $this->belongsTo(Invoice::class,'used_invoice_id');
    }

    public function items()
    {
        return $this->hasMany(ExchangeTicketItem::class);
    }
}
