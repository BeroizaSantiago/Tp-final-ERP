<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;

/**
 * Representa una coleccion o serie de libros, normalmente de una editorial.
 */
class Collection extends Model
{
    protected $guarded = [];

    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
