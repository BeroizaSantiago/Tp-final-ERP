<?php

namespace App\Services\Finance;

use App\Models\Finance\BankAccount;
use App\Models\Finance\BankConceptType;
use App\Models\Finance\BankMovement;
use Carbon\Carbon;

class BankMovementService
{
    public function create(array $data): BankMovement
    {
        if (! empty($data['issue_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim((string) $data['issue_date']))) {
            $time = now();
            $data['issue_date'] = Carbon::parse($data['issue_date'], config('app.timezone'))
                ->setTime($time->hour, $time->minute, $time->second);
        }

        if (!empty($data['bank_account_id'])) {
            $account = BankAccount::findOrFail($data['bank_account_id']);
            $data['bank_account_name'] = $account->bank_name.' - '.$account->account_number;
        }

        if (!empty($data['bank_concept_type_id'])) {
            $data['bank_concept_name'] = BankConceptType::find($data['bank_concept_type_id'])?->name;
        }

        if (!empty($data['bank_account_destination_id'])) {
            $destination = BankAccount::find($data['bank_account_destination_id']);
            $data['bank_account_destination_name'] = $destination?->bank_name.' - '.$destination?->account_number;
        }

        $data['creation_date'] ??= now();
        $data['created_by'] ??= auth()->user()?->name ?? 'system';
        $data['is_active'] ??= true;
        $data['reconciled'] ??= false;

        return BankMovement::create($data);
    }
}
