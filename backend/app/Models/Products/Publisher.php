<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;

/**
 * Representa una editorial: la casa que edita y publica un libro.
 */
class Publisher extends Model
{
    protected $guarded = [];

    public function collections()
    {
        return $this->hasMany(Collection::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
