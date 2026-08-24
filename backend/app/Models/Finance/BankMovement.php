<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Banco Movimiento.
 *
 * Representa la información persistida y las relaciones de Banco Movimiento dentro del ERP.
 */
class BankMovement extends Model
{
    protected $guarded = [];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function bankConceptType()
    {
        return $this->belongsTo(BankConceptType::class);
    }

    public function destinationAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_destination_id');
    }
}
