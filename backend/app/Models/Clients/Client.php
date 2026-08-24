<?php

namespace App\Models\Clients;

use App\Models\Sales\Invoice;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Cliente.
 *
 * Representa la información persistida y las relaciones de Cliente dentro del ERP.
 */
class Client extends Model
{
    protected $fillable = [
        'external_id',
        'code',
        'name',
        'birth_date',
        'document_type',
        'document_number',
        'vat_classification',
        'gross_income_classification',
        'fantasy_name',
        'state_tax_number',
        'payment_condition',
        'payment_condition_detail',
        'currency',
        'price_type',
        'discount',
        'credit_limit',
        'referred',
        'seller',
        'status',
        'last_payment_date',
        'related_provider',
        'is_wholesaler',
        'use_credit_invoice',
        'first_phone',
        'second_phone',
        'email',
        'address',
        'address_number',
        'apartment',
        'neighborhood',
        'zip_code',
        'city',
        'state',
        'country',
        'branch_origin',
        'notes',
        'is_active',
        'current_account_enabled',
        'payment_term_days',
        'current_account_surcharge_percentage',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'last_payment_date' => 'date',
        'discount' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'is_wholesaler' => 'boolean',
        'use_credit_invoice' => 'boolean',
        'is_active' => 'boolean',
        'current_account_enabled' => 'boolean',
        'payment_term_days' => 'integer',
        'current_account_surcharge_percentage' => 'decimal:4',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function receipts()
    {
        return $this->hasMany(CustomerReceipt::class);
    }
}
