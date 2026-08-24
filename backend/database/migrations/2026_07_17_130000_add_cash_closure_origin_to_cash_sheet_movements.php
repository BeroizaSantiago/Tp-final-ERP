<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_sheet_movements', function (Blueprint $table) {
            $table->foreignId('origin_cash_box_id')->nullable()->after('cash_box_id')->constrained('cash_boxes')->nullOnDelete();
            $table->foreignId('origin_cash_sheet_id')->nullable()->unique()->after('origin_cash_box_id')->constrained('cash_sheets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_sheet_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origin_cash_sheet_id');
            $table->dropConstrainedForeignId('origin_cash_box_id');
        });
    }
};
