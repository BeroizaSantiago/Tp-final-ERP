<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Factura Pago.
 *
 * Representa la información persistida y las relaciones de Factura Pago dentro del ERP.
 */
class InvoicePayment extends Model
{
    protected $fillable = [
        'invoice_id',
        'voucher_id',
        'payment_method',
        'status',
        'provider',
        'provider_order_id',
        'provider_payment_id',
        'external_reference',
        'idempotency_key',
        'qr_data',
        'provider_status_detail',
        'provider_payload',
        'expires_at',
        'approved_at',
        'cash_sheet_id',
        'treasury_cash_sheet_id',
        'created_by',
        'amount',
        'discount_amount',
        'surcharge_amount',
        'total_paid',
        'card_name',
        'card_plan',
        'card_surcharge_percentage',
        'bank_name',
        'reference',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'provider_payload' => 'array',
        'expires_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    /** Movimientos financieros que ubican el pago en una caja y planilla. */
    public function cashMovements()
    {
        return $this->hasMany(\App\Models\Finance\CashSheetMovement::class);
    }

    public function cashSheet()
    {
        return $this->belongsTo(\App\Models\Finance\CashSheet::class);
    }

    public function treasuryCashSheet()
    {
        return $this->belongsTo(\App\Models\Finance\CashSheet::class, 'treasury_cash_sheet_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
