<?php

namespace App\Models\Purchases;

use Illuminate\Database\Eloquent\Model;

/**
 * Representa un gasto vario registrado por comprobante.
 *
 * Puede vincularse a un proveedor y a un tipo de gasto para organizar
 * importes, impuestos, descuentos, percepciones y estado del gasto.
 */
class MiscExpense extends Model
{
    protected $guarded = [];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class);
    }
    public function payments()
    {
        return $this->hasMany(MiscExpensePayment::class);
    }
}
