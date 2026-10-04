<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;

/**
 * Representa al autor de un libro
 */
class Author extends Model
{
    protected $guarded = [];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}