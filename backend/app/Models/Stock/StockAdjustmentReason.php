<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Stock Ajuste Motivo.
 *
 * Representa la información persistida y las relaciones de Stock Ajuste Motivo dentro del ERP.
 */
class StockAdjustmentReason extends Model
{
    protected $fillable = ['name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
