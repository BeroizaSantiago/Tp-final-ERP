<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Producto Change Ítem.
 *
 * Representa la información persistida y las relaciones de Producto Change Ítem dentro del ERP.
 */
class ProductChangeItem extends Model
{
    protected $fillable = [
        'product_change_id',

        'refund_product_id',
        'delivered_product_id',

        'refund_quantity',
        'delivered_quantity',
    ];

    public function refundProduct()
    {
        return $this->belongsTo(Product::class, 'refund_product_id');
    }

    public function deliveredProduct()
    {
        return $this->belongsTo(Product::class, 'delivered_product_id');
    }
}
