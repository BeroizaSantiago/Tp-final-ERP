<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Banco Account.
 *
 * Representa la información persistida y las relaciones de Banco Account dentro del ERP.
 */
class BankAccount extends Model
{
    protected $guarded = [];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function bankBranch()
    {
        return $this->belongsTo(BankBranch::class);
    }
}
