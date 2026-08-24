<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;

/**
 * Representa el alcance especifico de una promocion de ventas.
 *
 * Permite aplicar una promocion a productos, categorias o marcas puntuales.
 */
class SalesPromotionItem extends Model
{
    protected $guarded = [];

    public function promotion()
    {
        return $this->belongsTo(SalesPromotion::class, 'sales_promotion_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
