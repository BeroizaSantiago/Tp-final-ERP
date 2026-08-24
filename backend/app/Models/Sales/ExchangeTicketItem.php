<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Cambio Ticket Ítem.
 *
 * Representa la información persistida y las relaciones de Cambio Ticket Ítem dentro del ERP.
 */
class ExchangeTicketItem extends Model
{
    protected $guarded = [];

    public function ticket()
    {
        return $this->belongsTo(ExchangeTicket::class,'exchange_ticket_id');
    }

    public function invoiceItem()
    {
        return $this->belongsTo(InvoiceItem::class);
    }
}
