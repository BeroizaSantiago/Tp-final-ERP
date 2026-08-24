<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Chequera.
 *
 * Representa la información persistida y las relaciones de Chequera dentro del ERP.
 */
class Checkbook extends Model
{
    protected $guarded = [];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }
}
