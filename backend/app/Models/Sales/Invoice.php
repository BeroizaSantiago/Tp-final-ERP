<?php

namespace App\Models\Sales;

use App\Models\Clients\Client;
use App\Models\Clients\CustomerReceiptApplication;
use App\Models\Finance\CardCoupon;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Factura.
 *
 * Representa la información persistida y las relaciones de Factura dentro del ERP.
 */
class Invoice extends Model
{
    protected $appends = [
        'is_internal_receipt',
        'display_number',
    ];

    protected $fillable = [
        'external_id',
        'issue_date',
        'payment_due_date',
        'receipt_type_name',
        'receipt_types_prefix',
        'letter',
        'first_number',
        'second_number',
        'full_number',
        'customer_external_id',
        'client_id',
        'customer_name',
        'customer_email',
        'customer_phone_number',
        'currency_name',
        'total_amount',
        'status_name',
        'status_id',
        'is_credit',
        'service_channel_id',
        'pos_type_id',
        'payment_condition_name',
        'printed',
        'authorization_code',
        'mode',
        'warehouse_id',
        'related_document_full_name',
        'count_associate_document',
        'updated_by',
        'seller_full_name',
        'user_name',
        'taxed_amount',
        'non_taxed_amount',
        'exempt_amount',
        'tax_amount',
        'gross_income_perception_amount',
        'gross_income_perception_percentage',
        'gross_income_tax_id',
        'global_discount_percentage',
        'net_global_discount_amount',
        'tax_global_discount_amount',
        'net_total_discount_product',
        'tax_total_discount_product',
        'net_recharge_amount',
        'tax_recharge_amount',
        'balance',
        'ecommerce_number',
        'ecommerce_shipping_method',
        'ecommerce_payment_method',
        'ecommerce_payment_method_card',
        'service_channel',
        'color_status_code',
        'color_status_description',
        'is_bill_without_delivery',
        'has_pending_delivery',
        'delivery_status',
        'is_print_ticket_for_change',
        'related_invoice_id',
        'credit_note_reason_id',
        'credit_note_reason_name',
        'credit_note_idempotency_key',
        'credit_note_stock_applied_at',
        'related_document_credit_note_id',
        'arca_status',
        'arca_result',
        'arca_cae',
        'arca_cae_expiration',
        'arca_voucher_number',
        'arca_voucher_type',
        'arca_point_of_sale',
        'arca_response',
        'stock_applied_at',
        'current_account_amount',
        'current_account_surcharge_amount',
        'sales_promotion_id',
        'promotion_discount_amount',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'payment_due_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'taxed_amount' => 'decimal:4',
        'non_taxed_amount' => 'decimal:4',
        'exempt_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'gross_income_perception_amount' => 'decimal:4',
        'gross_income_perception_percentage' => 'decimal:4',
        'global_discount_percentage' => 'decimal:4',
        'net_global_discount_amount' => 'decimal:4',
        'tax_global_discount_amount' => 'decimal:4',
        'net_total_discount_product' => 'decimal:4',
        'tax_total_discount_product' => 'decimal:4',
        'net_recharge_amount' => 'decimal:4',
        'tax_recharge_amount' => 'decimal:4',
        'balance' => 'decimal:2',
        'is_credit' => 'boolean',
        'printed' => 'boolean',
        'is_bill_without_delivery' => 'boolean',
        'is_print_ticket_for_change' => 'boolean',
        'arca_response' => 'array',
        'stock_applied_at' => 'datetime',
        'credit_note_stock_applied_at' => 'datetime',
        'arca_cae_expiration' => 'date',
        'current_account_amount' => 'decimal:2',
        'current_account_surcharge_amount' => 'decimal:2',
        'promotion_discount_amount' => 'decimal:4',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function getDocumentTypeAttribute()
    {
        return match ($this->receipt_types_prefix) {
            'FV' => 'Factura',
            'NC' => 'Nota de Crédito',
            'ND' => 'Nota de Débito',
            default => $this->receipt_type_name,
        };
    }

    public function getIsInternalReceiptAttribute(): bool
    {
        return $this->mode === 'internal'
            || str_pad((string) $this->first_number, 4, '0', STR_PAD_LEFT) === '0099';
    }

    public function getDisplayNumberAttribute(): string
    {
        if ($this->is_internal_receipt) {
            return preg_replace('/^INT-/', '', (string) ($this->full_number
                ?: '0099-' . str_pad((string) $this->id, 8, '0', STR_PAD_LEFT)));
        }

        return (string) ($this->full_number
            ?: trim(($this->letter ?? '') . '-' . ($this->first_number ?? ''), '-'));
    }

    public function getArcaReceiverDocumentTypeAttribute(): int
    {
        return (int) (data_get(
            $this->arca_response,
            'raw.FECAESolicitarResult.FeDetResp.FECAEDetResponse.DocTipo'
        ) ?? 99);
    }

    public function getArcaReceiverDocumentNumberAttribute(): int
    {
        return (int) (data_get(
            $this->arca_response,
            'raw.FECAESolicitarResult.FeDetResp.FECAEDetResponse.DocNro'
        ) ?? 0);
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function receiptApplications()
    {
        return $this->hasMany(CustomerReceiptApplication::class);
    }

    public function cardCoupons()
    {
        return $this->hasMany(CardCoupon::class);
    }

    public function salesPromotion()
    {
        return $this->belongsTo(\App\Models\Products\SalesPromotion::class);
    }

    public function originalInvoice()
    {
        return $this->belongsTo(self::class, 'related_invoice_id');
    }

    public function creditNotes()
    {
        return $this->hasMany(self::class, 'related_invoice_id')
            ->where('receipt_types_prefix', 'NC');
    }

    public function creditNoteReason()
    {
        return $this->belongsTo(CreditNoteReason::class);
    }
}
