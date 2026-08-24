<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_receipt_payments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('customer_receipt_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('payment_method');

            $table->decimal('amount',15,2);

            $table->string('card_name')->nullable();

            $table->string('card_plan')->nullable();

            $table->string('bank_name')->nullable();

            $table->string('reference')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_receipt_payments');
    }
};