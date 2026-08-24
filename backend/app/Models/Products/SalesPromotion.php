<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;

/**
 * Representa una promocion de ventas.
 *
 * Define vigencia, moneda, tipo de descuento, valor y alcance de aplicacion,
 * junto con los items especificos cuando corresponda.
 */
class SalesPromotion extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date_from' => 'date', 'date_to' => 'date', 'active_weekdays' => 'array',
        'payment_methods' => 'array', 'discount_value' => 'decimal:4',
        'minimum_amount' => 'decimal:4', 'minimum_quantity' => 'decimal:4', 'is_active' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(SalesPromotionItem::class);
    }
}
