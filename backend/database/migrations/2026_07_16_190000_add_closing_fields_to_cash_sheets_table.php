<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_sheets', function (Blueprint $table) {
            $table->decimal('opening_cash_amount', 15, 2)->default(0)->after('opening_date');

            $table->decimal('theoretical_cash', 15, 2)->nullable();
            $table->decimal('counted_cash', 15, 2)->nullable();
            $table->decimal('theoretical_credit_card', 15, 2)->nullable();
            $table->decimal('counted_credit_card', 15, 2)->nullable();
            $table->decimal('theoretical_debit_card', 15, 2)->nullable();
            $table->decimal('counted_debit_card', 15, 2)->nullable();
            $table->decimal('theoretical_transfer', 15, 2)->nullable();
            $table->decimal('counted_transfer', 15, 2)->nullable();
            $table->decimal('theoretical_checks', 15, 2)->nullable();
            $table->decimal('counted_checks', 15, 2)->nullable();
            $table->decimal('theoretical_total', 15, 2)->nullable();
            $table->decimal('counted_total', 15, 2)->nullable();
            $table->decimal('closing_difference', 15, 2)->nullable();
            $table->decimal('leave_in_cash', 15, 2)->default(0);

            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reopening_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cash_sheets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reopened_by');
            $table->dropConstrainedForeignId('closed_by');
            $table->dropColumn([
                'opening_cash_amount', 'theoretical_cash', 'counted_cash',
                'theoretical_credit_card', 'counted_credit_card',
                'theoretical_debit_card', 'counted_debit_card',
                'theoretical_transfer', 'counted_transfer',
                'theoretical_checks', 'counted_checks', 'theoretical_total',
                'counted_total', 'closing_difference', 'leave_in_cash',
                'reopened_at', 'reopening_reason',
            ]);
        });
    }
};
