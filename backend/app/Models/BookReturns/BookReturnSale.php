<?php

namespace App\Models\BookReturns;

use App\Models\Clients\Client;
use App\Models\Sales\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookReturnSale extends Model
{
    protected $guarded = [];

    protected $casts = [
        'return_date' => 'date',
        'stock_applied_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookReturnSaleItem::class);
    }
}