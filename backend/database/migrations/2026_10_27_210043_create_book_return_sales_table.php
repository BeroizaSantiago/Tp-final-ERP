<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_return_sales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->restrictOnDelete();

            $table->foreignId('client_id')
                ->nullable()
                ->constrained('clients')
                ->nullOnDelete();

            $table->date('return_date');
            $table->text('reason')->nullable();

            $table->string('status', 20)->default('pending');

            $table->string('idempotency_key', 64)->nullable()->unique();

            // Se marca solo cuando se aplicó el ingreso físico al inventario.
            $table->timestamp('stock_applied_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'return_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_return_sales');
    }
};