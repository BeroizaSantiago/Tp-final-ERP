<?php

namespace App\Models\BookReturns;

use App\Models\Purchases\PurchaseReceipt;
use App\Models\Purchases\Provider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookReturnPurchase extends Model
{
    protected $guarded = [];

    protected $casts = [
        'return_date' => 'date',
        'stock_applied_at' => 'datetime',
    ];

    public function purchaseReceipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookReturnPurchaseItem::class);
    }
}