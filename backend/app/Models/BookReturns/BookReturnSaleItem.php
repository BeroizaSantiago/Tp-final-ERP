<?php

namespace App\Models\BookReturns;

use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use App\Models\Sales\InvoiceItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookReturnSaleItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function bookReturnSale(): BelongsTo
    {
        return $this->belongsTo(BookReturnSale::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}