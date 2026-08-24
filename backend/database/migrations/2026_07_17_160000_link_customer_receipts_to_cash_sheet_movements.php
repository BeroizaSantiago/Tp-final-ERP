<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_sheet_movements', function (Blueprint $table) {
            $table->foreignId('customer_receipt_id')->nullable()->after('invoice_payment_id')
                ->constrained('customer_receipts')->nullOnDelete();
            $table->foreignId('customer_receipt_payment_id')->nullable()->unique()->after('customer_receipt_id')
                ->constrained('customer_receipt_payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_sheet_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_receipt_payment_id');
            $table->dropConstrainedForeignId('customer_receipt_id');
        });
    }
};
