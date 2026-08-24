<?php

namespace App\Models\Purchases;

use Illuminate\Database\Eloquent\Model;

/**
 * Representa una orden de compra emitida a un proveedor.
 *
 * Agrupa los items solicitados, el proveedor, la fecha, el total y el estado
 * administrativo de la orden.
 */
class PurchaseOrder extends Model
{
    protected $guarded = [];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
