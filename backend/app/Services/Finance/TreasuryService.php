<?php

namespace App\Services\Finance;

use App\Models\Finance\CashBox;
use App\Models\Finance\CashSheet;
use App\Models\Finance\CashSheetMovement;
use Illuminate\Validation\ValidationException;

class TreasuryService
{
    public function normalizeBranch(CashBox $cashBox, ?string $fallbackBranchName = null): CashBox
    {
        if (!$cashBox->branch_name && $fallbackBranchName) {
            $cashBox->branch_name = $fallbackBranchName;
        }

        if (!$cashBox->branch_id && $cashBox->branch_name) {
            $cashBox->branch_id = CashBox::query()
                ->where('box_type_name', 'TESORERIA')
                ->where('branch_name', $cashBox->branch_name)
                ->value('branch_id');
        }

        if ($cashBox->isDirty(['branch_id', 'branch_name'])) {
            $cashBox->save();
        }

        return $cashBox->refresh();
    }

    public function isTreasury(CashBox $cashBox): bool
    {
        return strtoupper((string) $cashBox->box_type_name) === 'TESORERIA';
    }

    public function openTreasuryFor(CashBox $cashBox): ?CashSheet
    {
        return CashSheet::query()
            ->where('status_name', 'Abierta')
            ->whereNull('closing_date')
            ->whereHas('cashBox', function ($query) use ($cashBox) {
                $query->where('box_type_name', 'TESORERIA');
                $this->sameBranch($query, $cashBox);
            })
            ->with('cashBox')
            ->lockForUpdate()
            ->first();
    }

    public function ensureTreasuryAllowsOpening(CashBox $cashBox): void
    {
        if ($this->isTreasury($cashBox)) {
            $alreadyOpen = CashSheet::query()->where('status_name', 'Abierta')->whereNull('closing_date')
                ->whereHas('cashBox', function ($query) use ($cashBox) {
                    $query->where('box_type_name', 'TESORERIA');
                    $this->sameBranch($query, $cashBox);
                })->exists();

            if ($alreadyOpen) {
                throw ValidationException::withMessages(['cash_box_id' => 'La Tesorería de esta sucursal ya se encuentra abierta.']);
            }
            return;
        }

        if (!$this->openTreasuryFor($cashBox)) {
            throw ValidationException::withMessages(['cash_box_id' => 'No se puede abrir la caja porque la Tesorería de la sucursal está cerrada.']);
        }
    }

    public function ensureTreasuryCanClose(CashSheet $treasurySheet): void
    {
        $cashBox = $treasurySheet->cashBox;
        if (!$cashBox || !$this->isTreasury($cashBox)) return;

        $openSalesBoxes = CashSheet::query()->where('id', '!=', $treasurySheet->id)
            ->where('status_name', 'Abierta')->whereNull('closing_date')
            ->whereHas('cashBox', function ($query) use ($cashBox) {
                $query->where(function ($type) { $type->whereNull('box_type_name')->orWhere('box_type_name', '!=', 'TESORERIA'); });
                $this->sameBranch($query, $cashBox);
            })->exists();

        if ($openSalesBoxes) {
            throw ValidationException::withMessages(['cash_sheet' => 'No se puede cerrar Tesorería mientras existan cajas de venta abiertas en la sucursal.']);
        }
    }

    public function receiveCashClosure(CashSheet $closedSheet, float $countedTotal, float $leaveInCash): CashSheetMovement
    {
        $originBox = $closedSheet->cashBox;
        $treasurySheet = $originBox ? $this->openTreasuryFor($originBox) : null;
        if (!$treasurySheet) {
            throw ValidationException::withMessages(['treasury' => 'La Tesorería debe estar abierta para recibir el cierre de caja.']);
        }

        $amount = round(max(0, $countedTotal - $leaveInCash), 2);

        return CashSheetMovement::create([
            'cash_sheet_id' => $treasurySheet->id,
            'cash_box_id' => $treasurySheet->cash_box_id,
            'origin_cash_box_id' => $closedSheet->cash_box_id,
            'origin_cash_sheet_id' => $closedSheet->id,
            'user_id' => auth()->id(),
            'movement_type' => 'income',
            'payment_method' => 'cash_closure',
            'affects_cash_balance' => true,
            'amount' => $amount,
            'document_number' => 'CIERRE-'.($closedSheet->number ?? $closedSheet->id),
            'reference' => 'Planilla #'.$closedSheet->id,
            'description' => 'Cierre recibido de '.$closedSheet->cash_box_name,
            'status' => 'active',
        ]);
    }

    private function sameBranch($query, CashBox $cashBox): void
    {
        $query->where(function ($branchQuery) use ($cashBox) {
            $hasBranch = false;

            if ($cashBox->branch_id) {
                $branchQuery->where('branch_id', $cashBox->branch_id);
                $hasBranch = true;
            }

            if ($cashBox->branch_name) {
                $method = $hasBranch ? 'orWhere' : 'where';
                $branchQuery->{$method}('branch_name', $cashBox->branch_name);
                $hasBranch = true;
            }

            if (!$hasBranch) {
                $branchQuery->whereRaw('1 = 0');
            }
        });
    }
}
