<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sheet_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cash_sheet_id')
                ->constrained('cash_sheets')
                ->cascadeOnDelete();

            $table->foreignId('cash_box_id')
                ->nullable()
                ->constrained('cash_boxes')
                ->nullOnDelete();

            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained('invoices')
                ->nullOnDelete();

            $table->foreignId('invoice_payment_id')
                ->nullable()
                ->constrained('invoice_payments')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('movement_type');
            $table->string('payment_method');

            /*
             * Efectivo modifica el dinero físico.
             * Tarjeta y transferencia se registran para el arqueo,
             * pero no incrementan el efectivo disponible.
             */
            $table->boolean('affects_cash_balance')
                ->default(false);

            $table->decimal('amount', 15, 2);

            $table->string('document_number')
                ->nullable();

            $table->string('customer_name')
                ->nullable();

            $table->string('reference')
                ->nullable();

            $table->text('description')
                ->nullable();

            $table->string('status')
                ->default('active');

            $table->timestamp('voided_at')
                ->nullable();

            $table->foreignId('voided_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('void_reason')
                ->nullable();

            $table->timestamps();

            $table->index([
                'cash_sheet_id',
                'payment_method',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sheet_movements');
    }
};