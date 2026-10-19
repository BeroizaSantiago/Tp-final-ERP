<?php

namespace App\Models\Products;

use App\Models\Products\Author;
use App\Models\Products\Collection;
use App\Models\Products\Publisher;
use Illuminate\Database\Eloquent\Model;

/**
 * Representa el alcance especifico de una promocion de ventas.
 *
 * Permite aplicar una promocion a productos, autores, categorias,
 * colecciones, editoriales o marcas puntuales.
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

    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
