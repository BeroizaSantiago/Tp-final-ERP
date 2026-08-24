<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;

/**
 * Representa un talle de producto asociado a un tipo de talle.
 */
class Size extends Model
{
    protected $guarded = [];

    public function sizeType()
    {
        return $this->belongsTo(SizeType::class);
    }
}
