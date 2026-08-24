<?php

namespace App\Models\Purchases;

use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use Illuminate\Database\Eloquent\Model;

/**
 * Representa un producto incluido en un remito de proveedor.
 *
 * Vincula el remito con el producto recibido y conserva su descripcion y
 * cantidad.
 */
class ProviderDeliveryNoteItem extends Model
{
    protected $guarded = [];

    public function providerDeliveryNote()
    {
        return $this->belongsTo(ProviderDeliveryNote::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
