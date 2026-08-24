<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Internal Transfer Ítem.
 *
 * Representa la información persistida y las relaciones de Internal Transfer Ítem dentro del ERP.
 */
class InternalTransferItem extends Model
{
    protected $guarded = [];

public function transfer()
{
    return $this->belongsTo(InternalTransfer::class, 'internal_transfer_id');
}

public function inventoryItem()
{
    return $this->belongsTo(InventoryItem::class);
}
}
