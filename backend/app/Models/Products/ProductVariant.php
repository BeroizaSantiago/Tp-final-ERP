<?php

namespace App\Models\Products;

use App\Models\Stock\InventoryItem;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Producto Variante.
 *
 * Representa la información persistida y las relaciones de Producto Variante dentro del ERP.
 */
class ProductVariant extends Model
{
    protected $guarded = [];

    protected $appends = ['image_full_url'];

    public function getImageFullUrlAttribute(): ?string
    {
        if (! $this->image_url) return null;

        return filter_var($this->image_url, FILTER_VALIDATE_URL)
            ? $this->image_url
            : url('media/' . $this->image_url);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function size()
    {
        return $this->belongsTo(Size::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'product_variant_id');
    }
}
