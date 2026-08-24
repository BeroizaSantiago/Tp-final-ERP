<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Crédito Nota Motivo.
 *
 * Representa la información persistida y las relaciones de Crédito Nota Motivo dentro del ERP.
 */
class CreditNoteReason extends Model
{
    protected $fillable = [
    'name',
    'is_active',
];

protected $casts = [
    'is_active' => 'boolean',
];
}
