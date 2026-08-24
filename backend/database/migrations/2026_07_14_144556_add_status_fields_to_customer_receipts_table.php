<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_receipts', function (Blueprint $table) {
            $table->string('status')
                ->default('active')
                ->after('notes');

            $table->string('origin')
                ->default('Cobro Cuenta Corriente')
                ->after('status');

            $table->string('created_by')
                ->nullable()
                ->after('origin');

            $table->timestamp('cancelled_at')
                ->nullable()
                ->after('created_by');

            $table->string('cancelled_by')
                ->nullable()
                ->after('cancelled_at');

            $table->text('cancellation_reason')
                ->nullable()
                ->after('cancelled_by');
        });
    }

    public function down(): void
    {
        Schema::table('customer_receipts', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'origin',
                'created_by',
                'cancelled_at',
                'cancelled_by',
                'cancellation_reason',
            ]);
        });
    }
};