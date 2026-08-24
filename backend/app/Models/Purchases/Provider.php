<?php

namespace App\Models\Purchases;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Proveedor.
 *
 * Representa la información persistida y las relaciones de Proveedor dentro del ERP.
 */
class Provider extends Model
{
    protected $fillable = [
        'external_id',
        'code',
        'name',
        'fantasy_name',
        'document_type',
        'identification_number',
        'vat_classification',
        'address',
        'city_name',
        'province_name',
        'gross_income_number',
        'primary_phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function paymentOrders()
    {
        return $this->hasMany(ProviderPaymentOrder::class);
    }

}
