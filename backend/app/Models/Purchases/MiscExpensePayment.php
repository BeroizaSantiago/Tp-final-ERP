<?php

namespace App\Models\Purchases;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Vario Gasto Pago.
 *
 * Representa la información persistida y las relaciones de Vario Gasto Pago dentro del ERP.
 */
class MiscExpensePayment extends Model
{
    protected $guarded = [];

    public function miscExpense()
    {
        return $this->belongsTo(MiscExpense::class);
    }
}
