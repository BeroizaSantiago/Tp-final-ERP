<?php

namespace App\Models\Purchases;

use Illuminate\Database\Eloquent\Model;

/**
 * Representa un comprobante de compra recibido de un proveedor.
 *
 * Agrupa los items comprados, el proveedor, los datos del comprobante,
 * importes totales y estado de registracion.
 */
class PurchaseReceipt extends Model
{
    protected $guarded = [];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseReceiptItem::class);
}
}
