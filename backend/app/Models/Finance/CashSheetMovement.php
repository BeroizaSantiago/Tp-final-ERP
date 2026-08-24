<?php

namespace App\Models\Finance;

use App\Models\Sales\Invoice;
use App\Models\Sales\InvoicePayment;
use App\Models\Clients\CustomerReceipt;
use App\Models\Clients\CustomerReceiptPayment;
use App\Models\Purchases\ProviderPaymentOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Caja Planilla Movimiento.
 *
 * Representa la información persistida y las relaciones de Caja Planilla Movimiento dentro del ERP.
 */
class CashSheetMovement extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'affects_cash_balance' => 'boolean',
        'voided_at' => 'datetime',
    ];

    public function cashSheet()
    {
        return $this->belongsTo(CashSheet::class);
    }

    public function cashBox()
    {
        return $this->belongsTo(CashBox::class);
    }

    public function originCashBox()
    {
        return $this->belongsTo(CashBox::class, 'origin_cash_box_id');
    }

    public function originCashSheet()
    {
        return $this->belongsTo(CashSheet::class, 'origin_cash_sheet_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function invoicePayment()
    {
        return $this->belongsTo(InvoicePayment::class);
    }

    public function customerReceipt()
    {
        return $this->belongsTo(CustomerReceipt::class);
    }

    public function customerReceiptPayment()
    {
        return $this->belongsTo(CustomerReceiptPayment::class);
    }

    public function providerPaymentOrder()
    {
        return $this->belongsTo(ProviderPaymentOrder::class);
    }

    public function reversedMovement()
    {
        return $this->belongsTo(self::class, 'reversal_of_movement_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function voidedByUser()
    {
        return $this->belongsTo(
            User::class,
            'voided_by'
        );
    }
}
