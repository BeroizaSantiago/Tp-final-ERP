<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;

/**
 * Representa un grupo o familia de talles.
 */
class SizeType extends Model
{
    protected $guarded = [];

    public function sizes()
    {
        return $this->hasMany(Size::class);
    }
}
