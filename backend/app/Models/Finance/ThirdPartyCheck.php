<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Third Party Check.
 *
 * Representa la información persistida y las relaciones de Third Party Check dentro del ERP.
 */
class ThirdPartyCheck extends Model
{
    protected $guarded = [];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
