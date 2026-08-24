<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Stock Movimiento.
 *
 * Representa la información persistida y las relaciones de Stock Movimiento dentro del ERP.
 */
class StockMovement extends Model
{
    protected $guarded = [];
    protected $fillable = [
    'inventory_item_id',
    'movement_type',
    'quantity',
    'stock_before',
    'stock_after',
    'reference_type',
    'reference_id',
    'notes',
];

    protected $casts = ['quantity'=>'decimal:4','stock_before'=>'decimal:4','stock_after'=>'decimal:4'];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
