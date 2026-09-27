<?php

namespace App\Models\Products;

use App\Models\Stock\InventoryItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modelo de Producto Variante.
 *
 * Representa la información persistida y las relaciones de Producto Variante dentro del ERP.
 *
 * Las variantes se definen por la combinación de atributos maestros:
 * - Categoría
 * - Marca
 * - Editorial
 * - Modelo
 * - Colección
 *
 * Cada combinación única de estos atributos crea una variante distinta que puede
 * tener su propio stock, SKU y código de barras.
 */
class ProductVariant extends Model
{
    protected $guarded = [];

    protected $appends = ['image_full_url'];

    public function getImageFullUrlAttribute(): ?string
    {
        if (! $this->image_url) return null;

        return filter_var($this->image_url, FILTER_VALIDATE_URL)
            ? $this->image_url
            : url('media/' . $this->image_url);
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

    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }

    public function model()
    {
        return $this->belongsTo(ProductModel::class, 'product_model_id');
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'product_variant_id');
    }

    /**
     * Busca una variante por su combinación de atributos maestros.
     *
     * @param int $productId
     * @param array $attrs Claves posibles: category_id, brand_id, publisher_id, product_model_id, collection_id
     */
    public static function findByAttributes(int $productId, array $attrs): ?self
    {
        $fillable = ['category_id', 'brand_id', 'publisher_id', 'product_model_id', 'collection_id'];

        return self::query()
            ->where('product_id', $productId)
            ->where(function (Builder $query) use ($attrs, $fillable) {
                foreach ($fillable as $field) {
                    $value = $attrs[$field] ?? null;

                    if ($value !== null && $value !== '') {
                        $query->where($field, (int) $value);
                    } else {
                        $query->whereNull($field);
                    }
                }
            })
            ->first();
    }

    /**
     * Devuelve un array con los atributos que definen esta variante.
     */
    public function attributeValues(): array
    {
        return [
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'publisher_id' => $this->publisher_id,
            'product_model_id' => $this->product_model_id,
            'collection_id' => $this->collection_id,
        ];
    }

    /**
     * Genera una descripción legible de la combinación de atributos.
     */
    public function attributeLabel(): string
    {
        $parts = [];

        if ($this->relationLoaded('category') && $this->category) {
            $parts[] = $this->category->name;
        }
        if ($this->relationLoaded('brand') && $this->brand) {
            $parts[] = $this->brand->name;
        }
        if ($this->relationLoaded('publisher') && $this->publisher) {
            $parts[] = $this->publisher->name;
        }
        if ($this->relationLoaded('model') && $this->model) {
            $parts[] = $this->model->name;
        }
        if ($this->relationLoaded('collection') && $this->collection) {
            $parts[] = $this->collection->name;
        }

        return empty($parts) ? 'Variante sin atributos' : implode(' · ', $parts);
    }
}
