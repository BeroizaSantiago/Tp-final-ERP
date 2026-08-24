<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cash_boxes')
            ->whereNull('branch_name')
            ->orderBy('id')
            ->eachById(function ($cashBox) {
                $branchName = DB::table('cash_sheets')
                    ->where('cash_box_id', $cashBox->id)
                    ->whereNotNull('branch_name')
                    ->latest('id')
                    ->value('branch_name');

                if (!$branchName) return;

                $branchId = DB::table('cash_boxes')
                    ->where('box_type_name', 'TESORERIA')
                    ->where('branch_name', $branchName)
                    ->value('branch_id');

                DB::table('cash_boxes')->where('id', $cashBox->id)->update([
                    'branch_name' => $branchName,
                    'branch_id' => $branchId,
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        // Completa configuración faltante a partir de datos históricos.
    }
};
