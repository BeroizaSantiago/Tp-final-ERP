<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Stock Ajuste.
 *
 * Representa la información persistida y las relaciones de Stock Ajuste dentro del ERP.
 */
class StockAdjustment extends Model
{
    protected $guarded = [];

    public function reason()
    {
        return $this->belongsTo(StockAdjustmentReason::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class, 'reference_id')
            ->where('reference_type', 'stock_adjustment');
    }
}
