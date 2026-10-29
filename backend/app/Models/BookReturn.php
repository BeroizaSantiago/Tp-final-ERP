<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de Devolución de Libros.
 *
 * Representa una devolución de libros ya sea a clientes o proveedores.
 */
class BookReturn extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
        'condition' => 'string',
        'status' => 'string',
    ];

    /**
     * Get the product that is being returned.
     */
    public function returnable()
    {
        return $this->morphTo();
    }

    /**
     * Get the client associated with the return (if applicable).
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Get the provider associated with the return (if applicable).
     */
    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    /**
     * Get the invoice associated with the return (if applicable).
     */
    public function invoice()
    {
        return $this->belongsTo(\App\Models\Sales\Invoice::class, 'invoice_id');
    }

    /**
     * Get the purchase associated with the return (if applicable).
     */
    public function purchase()
    {
        return $this->belongsTo(\App\Models\Purchases\Purchase::class, 'purchase_id');
    }

    /**
     * Scope to filter by return type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('return_type', $type);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}