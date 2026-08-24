<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->foreignId('treasury_cash_sheet_id')
                ->nullable()
                ->after('cash_sheet_id')
                ->constrained('cash_sheets')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('treasury_cash_sheet_id');
        });
    }
};
