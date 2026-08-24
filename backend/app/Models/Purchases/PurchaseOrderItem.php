<?php

namespace App\Models\Purchases;

use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Model;

/**
 * Representa un producto incluido en una orden de compra.
 *
 * Vincula la orden con el producto solicitado y conserva cantidad, precio
 * unitario y total de la linea.
 */
class PurchaseOrderItem extends Model
{
    protected $guarded = [];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
