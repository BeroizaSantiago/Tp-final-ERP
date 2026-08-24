<?php

namespace App\Models\Sales;

use App\Models\Products\ProductVariant;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Factura Ítem.
 *
 * Representa la información persistida y las relaciones de Factura Ítem dentro del ERP.
 */
class InvoiceItem extends Model
{
    protected $guarded = [];
   protected $fillable = [
    'invoice_id',
    'original_invoice_item_id',
    'product_id',
    'external_id',
    'product_search_code',
    'product_external_id',
    'product_variant_id',
    'product_variant_external_id',
    'size_id',
    'size_name',
    'color_id',
    'color_name',
    'description',
    'quantity',
    'pending',
    'tax_aliquot_id',
    'tax_aliquot_percentage',
    'unit_price_with_taxes',
    'unit_price',
    'discount_percentage',
    'manual_discount_percentage',
    'promotion_discount_amount',
    'discount_amount',
    'subtotal_amount',
    'tax_amount',
    'total_amount',
    'warehouse_id',
    'notes',
    'is_promotion',
    'sales_promotion_id',
    'product_name',
    'product_code',
    'product_barcode',
    'product_reference_code',
    'product_display_text',
    'product_category_id',
    'product_category_name',
    'brand_name',
    'model_name',
    'variant_barcode',
    'variant_price_a',
    'variant_price_b',
    'variant_price_c',
    'variant_price_d',
    'is_active',
];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Products\Product::class);
    }

    public function originalInvoiceItem()
    {
        return $this->belongsTo(self::class, 'original_invoice_item_id');
    }

    public function creditedItems()
    {
        return $this->hasMany(self::class, 'original_invoice_item_id');
    }
}
