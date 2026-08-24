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
    Schema::create('card_coupons', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('external_id')->nullable()->index();

        $table->string('credit_card')->nullable();
        $table->unsignedBigInteger('credit_card_id')->nullable();

        $table->string('last_digits_card')->nullable();

        $table->string('credit_card_plan')->nullable();
        $table->unsignedBigInteger('credit_card_plan_id')->nullable();

        $table->string('lot_number')->nullable();
        $table->string('coupon_number')->nullable()->index();

        $table->integer('instalments')->default(1);

        $table->string('coupon_status')->default('Pendiente');
        $table->integer('status_id')->nullable();

        $table->decimal('coupon_amount', 15, 4)->default(0);
        $table->decimal('commission', 15, 4)->default(0);
        $table->decimal('charge_amount', 15, 4)->default(0);

        $table->dateTime('creation_date')->nullable();
        $table->dateTime('expected_date')->nullable();

        $table->string('credit_card_type')->nullable();

        $table->string('receipt_number')->nullable();
        $table->string('customer_name')->nullable();

        $table->string('trade_number')->nullable();

        $table->unsignedBigInteger('currency_id')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_coupons');
    }
};
