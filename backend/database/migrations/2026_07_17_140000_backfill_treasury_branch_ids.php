<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cash_boxes')
            ->where('box_type_name', 'TESORERIA')
            ->whereNull('branch_id')
            ->whereNotNull('branch_name')
            ->orderBy('id')
            ->eachById(function ($treasury) {
                $branchId = DB::table('cash_boxes')
                    ->where('id', '!=', $treasury->id)
                    ->where('branch_name', $treasury->branch_name)
                    ->whereNotNull('branch_id')
                    ->value('branch_id');

                if ($branchId) {
                    DB::table('cash_boxes')->where('id', $treasury->id)->update([
                        'branch_id' => $branchId,
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // La normalización completa un dato faltante y no debe deshacerse.
    }
};
