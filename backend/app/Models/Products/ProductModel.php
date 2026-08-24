<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;

/**
 * Representa un modelo de producto asociado a una marca.
 */
class ProductModel extends Model
{
    protected $guarded = [];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
