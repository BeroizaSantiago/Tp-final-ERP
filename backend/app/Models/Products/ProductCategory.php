<?php

namespace App\Models\Products;

use App\Models\IA\AiEnrichment;
use Illuminate\Database\Eloquent\Model;

/**
 * Representa una categoria de productos con jerarquia padre-hijo.
 */
class ProductCategory extends Model
{
    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    public function aiEnrichments()
    {
        return $this->morphMany(AiEnrichment::class, 'enrichable');
    }
}
