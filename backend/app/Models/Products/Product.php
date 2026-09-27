<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;
use App\Models\Stock\InventoryItem;

/**
 * Modelo de Producto.
 *
 * Representa la información persistida y las relaciones de Producto dentro del ERP.
 */
class Product extends Model
{
   protected $guarded = [];

   protected $casts = [
      'has_variants' => 'boolean',
      'is_active' => 'boolean',
      'extended_info' => 'array',
      'woocommerce_last_synced_at' => 'datetime',
   ];

   protected $appends = ['image_full_url'];

   public function getImageFullUrlAttribute(): ?string
   {
      if (! $this->image_url) {
         return null;
      }

      return filter_var($this->image_url, FILTER_VALIDATE_URL)
         ? $this->image_url
         : url('media/' . $this->image_url);
   }

   public function images()
   {
      return $this->hasMany(ProductImage::class)->orderBy('position')->orderBy('id');
   }

   public function category()
   {
      return $this->belongsTo(ProductCategory::class);
   }

   public function brand()
   {
      return $this->belongsTo(Brand::class);
   }

   public function publisher()
   {
      return $this->belongsTo(Publisher::class);
   }

   public function collection()
   {
      return $this->belongsTo(Collection::class);
   }

   public function model()
   {
      return $this->belongsTo(ProductModel::class, 'product_model_id');
   }

   public function size()
   {
      return $this->belongsTo(Size::class);
   }

   public function color()
   {
      return $this->belongsTo(Color::class);
   }
   public function inventoryItems()
   {
      return $this->hasMany(InventoryItem::class, 'product_id');
   }
      public function variants()
   {
      return $this->hasMany(ProductVariant::class);
   }
}
