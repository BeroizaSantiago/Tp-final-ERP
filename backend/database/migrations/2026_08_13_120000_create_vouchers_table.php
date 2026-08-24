<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->decimal('amount', 15, 2);
            $table->text('message')->nullable();
            $table->enum('delivery_type', ['physical', 'digital'])->default('physical');
            $table->enum('sales_channel', ['store', 'online'])->default('store');
            $table->enum('status', ['available', 'reserved', 'used', 'disabled'])->default('available');
            $table->date('expires_at')->nullable();
            $table->foreignId('reserved_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamp('reserved_at')->nullable();
            $table->foreignId('used_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
        });

        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->foreignId('voucher_id')->nullable()->after('invoice_id')
                ->constrained('vouchers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voucher_id');
        });
        Schema::dropIfExists('vouchers');
    }
};
