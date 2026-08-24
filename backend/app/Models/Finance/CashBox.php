<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Caja Box.
 *
 * Representa la información persistida y las relaciones de Caja Box dentro del ERP.
 */
class CashBox extends Model
{
    protected $guarded = [];

    public function currencies()
    {
        return $this->hasMany(CashCurrency::class);
    }

    public function pointOfSales()
    {
        return $this->hasMany(CashPointOfSale::class);
    }

    public function cashSheets()
    {
        return $this->hasMany(CashSheet::class);
    }
}
