<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_return_purchases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_receipt_id')
                ->constrained('purchase_receipts')
                ->restrictOnDelete();

            $table->foreignId('provider_id')
                ->nullable()
                ->constrained('providers')
                ->nullOnDelete();

            $table->date('return_date');
            $table->text('reason')->nullable();

            $table->string('status', 20)->default('pending');

            $table->string('idempotency_key', 64)->nullable()->unique();

            // Evita aplicar el mismo movimiento de stock más de una vez.
            $table->timestamp('stock_applied_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'return_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_return_purchases');
    }
};