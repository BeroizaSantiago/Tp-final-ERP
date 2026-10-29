<?php

namespace App\Models\BookReturns;

use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use App\Models\Purchases\PurchaseReceiptItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookReturnPurchaseItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function bookReturnPurchase(): BelongsTo
    {
        return $this->belongsTo(BookReturnPurchase::class);
    }

    public function purchaseReceiptItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceiptItem::class);
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