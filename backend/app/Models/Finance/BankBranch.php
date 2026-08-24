<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Banco Sucursal.
 *
 * Representa la información persistida y las relaciones de Banco Sucursal dentro del ERP.
 */
class BankBranch extends Model
{
    protected $guarded = [];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
