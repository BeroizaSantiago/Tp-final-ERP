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
    Schema::create('purchase_payments', function (Blueprint $table) {
        $table->id();

        $table->foreignId('purchase_id')
            ->constrained('purchases')
            ->cascadeOnDelete();

        $table->string('payment_method'); // cash, transfer, third_party_check, own_check, supplier_account, retention
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
        Schema::dropIfExists('purchase_payments');
    }
};
