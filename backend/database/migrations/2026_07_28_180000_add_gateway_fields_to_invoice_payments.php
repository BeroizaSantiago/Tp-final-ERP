<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->string('status')->default('approved')->after('payment_method')->index();
            $table->string('provider')->nullable()->after('status')->index();
            $table->string('provider_order_id')->nullable()->after('provider')->unique();
            $table->string('provider_payment_id')->nullable()->after('provider_order_id')->index();
            $table->string('external_reference')->nullable()->after('provider_payment_id')->unique();
            $table->uuid('idempotency_key')->nullable()->after('external_reference')->unique();
            $table->longText('qr_data')->nullable()->after('idempotency_key');
            $table->string('provider_status_detail')->nullable()->after('qr_data');
            $table->json('provider_payload')->nullable()->after('provider_status_detail');
            $table->timestamp('expires_at')->nullable()->after('provider_payload')->index();
            $table->timestamp('approved_at')->nullable()->after('expires_at');
            $table->foreignId('cash_sheet_id')->nullable()->after('approved_at')
                ->constrained('cash_sheets')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('cash_sheet_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('cash_sheet_id');
            $table->dropColumn([
                'status',
                'provider',
                'provider_order_id',
                'provider_payment_id',
                'external_reference',
                'idempotency_key',
                'qr_data',
                'provider_status_detail',
                'provider_payload',
                'expires_at',
                'approved_at',
            ]);
        });
    }
};
