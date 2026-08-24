<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $guarded = [];
    protected $appends = ['effective_status'];

    protected $casts = [
        'amount' => 'decimal:2',
        'maximum_discount_amount' => 'decimal:2',
        'expires_at' => 'date',
        'reserved_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function reservedInvoice() { return $this->belongsTo(Invoice::class, 'reserved_invoice_id'); }
    public function usedInvoice() { return $this->belongsTo(Invoice::class, 'used_invoice_id'); }
    public function creator() { return $this->belongsTo(\App\Models\User::class, 'created_by'); }

    public function isExpired(): bool
    {
        return $this->expires_at && now()->startOfDay()->greaterThan($this->expires_at->startOfDay());
    }

    public function getEffectiveStatusAttribute(): string
    {
        return $this->isExpired() && $this->status === 'available' ? 'expired' : $this->status;
    }

    public function applicableAmount(float $saleTotal): float
    {
        if ($this->value_type !== 'percentage') {
            return round((float) $this->amount, 2);
        }

        $amount = max(0, $saleTotal) * ((float) $this->amount / 100);
        if ($this->maximum_discount_amount !== null) {
            $amount = min($amount, (float) $this->maximum_discount_amount);
        }

        return round($amount, 2);
    }
}
