<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Internal Transfer.
 *
 * Representa la información persistida y las relaciones de Internal Transfer dentro del ERP.
 */
class InternalTransfer extends Model
{  
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(InternalTransferItem::class);
    }
}
