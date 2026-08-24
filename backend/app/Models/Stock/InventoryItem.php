<?php

namespace App\Models\Stock;

use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Inventario Ítem.
 *
 * Representa la información persistida y las relaciones de Inventario Ítem dentro del ERP.
 */
class InventoryItem extends Model
{
    protected $guarded = [];
/*     protected $fillable = [
        'product_external_id',
        'product_variant_external_id',
        'code',
        'bar_code',
        'reference_code',
        'company_configuration_code',
        'product_name',
        'branch_name',
        'warehouse_name',
        'min_stock',
        'reposition_stock',
        'current_stock',
        'company_id',
        'account_id',
        'color_name',
        'size_name',
        'stock_batch',
        'valued_item',
        'currency_id',
        'currency_symbol',
    ]; */
    public function product()
{
    return $this->belongsTo(Product::class);
}

public function variant()
{
    return $this->belongsTo(ProductVariant::class, 'product_variant_id');
}

public function stockMovements()
{
    return $this->hasMany(StockMovement::class);
}
}
