<?php

namespace App\Models\Products;

use App\Models\Clients\Client;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Producto Change.
 *
 * Representa la información persistida y las relaciones de Producto Change dentro del ERP.
 */
class ProductChange extends Model
{
    protected $fillable = [
        'client_id',
        'customer_name',
        'reason_type_name',
        'notes',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(ProductChangeItem::class);
    }
}
