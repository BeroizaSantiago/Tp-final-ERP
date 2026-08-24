<?php

namespace App\Models\Purchases;

use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Compra Ítem.
 *
 * Representa la información persistida y las relaciones de Compra Ítem dentro del ERP.
 */
class PurchaseItem extends Model
{
    protected $guarded = [];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
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
