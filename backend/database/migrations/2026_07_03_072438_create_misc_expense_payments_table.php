<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('misc_expense_payments', function (Blueprint $table) {
        $table->id();

        $table->foreignId('misc_expense_id')
            ->constrained('misc_expenses')
            ->cascadeOnDelete();

        $table->string('payment_method');
        $table->decimal('amount', 15, 4)->default(0);
        $table->decimal('discount_amount', 15, 4)->default(0);
        $table->decimal('surcharge_amount', 15, 4)->default(0);
        $table->decimal('total_paid', 15, 4)->default(0);

        $table->string('bank_name')->nullable();
        $table->string('reference')->nullable();
        $table->text('notes')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('misc_expense_payments');
    }
};
