<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('current_account_amount', 15, 2)
                ->default(0)
                ->after('balance');

            $table->decimal('current_account_surcharge_amount', 15, 2)
                ->default(0)
                ->after('current_account_amount');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'current_account_amount',
                'current_account_surcharge_amount',
            ]);
        });
    }
};