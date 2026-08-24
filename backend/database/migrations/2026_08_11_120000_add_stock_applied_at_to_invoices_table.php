<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('stock_applied_at')->nullable()->after('arca_response');
        });

        // Los comprobantes anteriores a esta mejora ya afectaron stock con el
        // flujo histórico; se marcan para no volver a descontarlos.
        DB::table('invoices')->whereNull('stock_applied_at')->update([
            'stock_applied_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('stock_applied_at');
        });
    }
};
