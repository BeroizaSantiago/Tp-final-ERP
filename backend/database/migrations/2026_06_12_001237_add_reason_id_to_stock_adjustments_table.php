<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vincula los ajustes de stock con un motivo normalizado.
        Schema::table('stock_adjustments', function (Blueprint $table) {
        $table->foreignId('reason_id')
            ->nullable()
            ->after('adjustment_stock_reason')
            ->constrained('stock_adjustment_reasons')
            ->nullOnDelete();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            //
        });
    }
};
