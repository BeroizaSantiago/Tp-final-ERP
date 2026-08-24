<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Caja Planilla.
 *
 * Representa la información persistida y las relaciones de Caja Planilla dentro del ERP.
 */
class CashSheet extends Model
{
    protected $guarded = [];

    protected $casts = [
        'opening_date' => 'datetime',
        'closing_date' => 'datetime',
        'reopened_at' => 'datetime',
        'opening_cash_amount' => 'decimal:2',
        'theoretical_cash' => 'decimal:2',
        'counted_cash' => 'decimal:2',
        'theoretical_total' => 'decimal:2',
        'counted_total' => 'decimal:2',
        'closing_difference' => 'decimal:2',
        'leave_in_cash' => 'decimal:2',
    ];

    public function cashBox()
    {
        return $this->belongsTo(CashBox::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movements()
    {
        return $this->hasMany(
            CashSheetMovement::class
        );
    }

    public function activeMovements()
    {
        return $this->hasMany(
            CashSheetMovement::class
        )->where('status', 'active');
    }

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopenedByUser()
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }
}
