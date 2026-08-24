<?php

namespace App\Models\Clients;

use App\Models\Sales\Invoice;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Cliente Recibo Aplicación.
 *
 * Representa la información persistida y las relaciones de Cliente Recibo Aplicación dentro del ERP.
 */
class CustomerReceiptApplication extends Model
{
    protected $fillable = [
        'customer_receipt_id',
        'invoice_id',
        'amount',
    ];

    protected $casts = [
        'amount'=>'decimal:2'
    ];

    public function receipt()
    {
        return $this->belongsTo(CustomerReceipt::class,'customer_receipt_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
