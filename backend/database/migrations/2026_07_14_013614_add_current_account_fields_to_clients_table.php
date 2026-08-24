<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('current_account_enabled')
                ->default(false)
                ->after('use_credit_invoice');

            $table->unsignedInteger('payment_term_days')
                ->default(0)
                ->after('current_account_enabled');

            $table->decimal(
                'current_account_surcharge_percentage',
                8,
                4
            )
                ->default(0)
                ->after('payment_term_days');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'current_account_enabled',
                'payment_term_days',
                'current_account_surcharge_percentage',
            ]);
        });
    }
};