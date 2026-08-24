<?php

namespace App\Models\Purchases;

use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Model;

/**
 * Representa una linea de producto dentro de un comprobante de compra.
 *
 * Guarda cantidad, precio, impuesto y total asociado al producto recibido.
 */
class PurchaseReceiptItem extends Model
{
    protected $guarded = [];

    public function purchaseReceipt()
    {
        return $this->belongsTo(PurchaseReceipt::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
