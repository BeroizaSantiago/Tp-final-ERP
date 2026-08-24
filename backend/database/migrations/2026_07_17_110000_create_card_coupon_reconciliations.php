<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permite reintentar la migración si MySQL interrumpió un DDL parcial.
        Schema::dropIfExists('card_coupon_reconciliation_items');
        Schema::dropIfExists('card_coupon_reconciliations');

        Schema::create('card_coupon_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('settlement_number')->nullable()->index();
            $table->dateTime('issue_date');
            $table->dateTime('accreditation_date');
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
            $table->decimal('gross_amount', 15, 4);
            $table->decimal('commission_amount', 15, 4)->default(0);
            $table->decimal('withholding_amount', 15, 4)->default(0);
            $table->string('withholding_description')->nullable();
            $table->decimal('other_discount_amount', 15, 4)->default(0);
            $table->string('other_discount_description')->nullable();
            $table->decimal('net_amount', 15, 4);
            $table->string('status')->default('Confirmada');
            $table->text('notes')->nullable();
            $table->foreignId('bank_movement_id')->nullable()->unique()->constrained('bank_movements')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('card_coupon_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('card_coupon_reconciliation_id');
            $table->unsignedBigInteger('card_coupon_id')->unique();
            $table->decimal('coupon_amount', 15, 4);
            $table->decimal('commission_amount', 15, 4)->default(0);
            $table->decimal('net_amount', 15, 4);
            $table->timestamps();

            $table->foreign('card_coupon_reconciliation_id', 'ccri_reconciliation_fk')
                ->references('id')->on('card_coupon_reconciliations')->cascadeOnDelete();
            $table->foreign('card_coupon_id', 'ccri_coupon_fk')
                ->references('id')->on('card_coupons')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_coupon_reconciliation_items');
        Schema::dropIfExists('card_coupon_reconciliations');
    }
};
