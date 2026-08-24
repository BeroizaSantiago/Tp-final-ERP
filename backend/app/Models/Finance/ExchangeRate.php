<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Cambio Rate.
 *
 * Representa la información persistida y las relaciones de Cambio Rate dentro del ERP.
 */
class ExchangeRate extends Model
{
    protected $fillable = [
        'source_currency',
        'target_currency',
        'exchange_rate',
        'quote_date',
    ];
}
