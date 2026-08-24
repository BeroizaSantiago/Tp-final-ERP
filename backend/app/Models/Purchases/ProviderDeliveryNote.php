<?php

namespace App\Models\Purchases;

use Illuminate\Database\Eloquent\Model;

/**
 * Representa un remito emitido por un proveedor.
 *
 * Agrupa productos recibidos, cantidad total, proveedor, estado y si queda
 * pendiente la factura correspondiente.
 */
class ProviderDeliveryNote extends Model
{
    protected $guarded = [];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function items()
    {
        return $this->hasMany(ProviderDeliveryNoteItem::class);
    }
}
