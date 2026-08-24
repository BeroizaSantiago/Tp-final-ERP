<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::create('invoice_payments', function (Blueprint $table) {
        $table->id();

        $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();

        $table->string('payment_method'); 
        // cash, credit_card, debit_card, transfer, checking_account

        $table->decimal('amount', 15, 2);
        $table->decimal('discount_amount', 15, 2)->default(0);
        $table->decimal('surcharge_amount', 15, 2)->default(0);
        $table->decimal('total_paid', 15, 2);

        $table->string('card_name')->nullable();
        $table->string('card_plan')->nullable();
        $table->decimal('card_surcharge_percentage', 8, 4)->default(0);

        $table->string('bank_name')->nullable();
        $table->string('reference')->nullable();

        $table->text('notes')->nullable();

        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('invoice_payments');
}
};
