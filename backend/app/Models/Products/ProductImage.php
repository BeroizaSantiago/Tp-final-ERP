<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Producto Image.
 *
 * Representa la información persistida y las relaciones de Producto Image dentro del ERP.
 */
class ProductImage extends Model
{
    protected $fillable = ['product_id', 'path', 'position'];

    protected $appends = ['full_url'];

    public function getFullUrlAttribute(): string
    {
        return filter_var($this->path, FILTER_VALIDATE_URL)
            ? $this->path
            : url('media/'.$this->path);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
