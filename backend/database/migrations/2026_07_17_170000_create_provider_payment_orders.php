<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_payment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->restrictOnDelete();
            $table->string('number')->unique();
            $table->date('issue_date');
            $table->decimal('total_amount', 15, 4);
            $table->string('payment_method');
            $table->string('origin')->default('current_account');
            $table->string('bank_name')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index(['provider_id', 'issue_date']);
            $table->index(['origin', 'status']);
        });

        Schema::create('provider_payment_order_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_payment_order_id');
            $table->foreign('provider_payment_order_id', 'ppo_app_order_fk')
                ->references('id')->on('provider_payment_orders')->cascadeOnDelete();
            $table->foreignId('purchase_id')->constrained('purchases')->restrictOnDelete();
            $table->foreignId('purchase_payment_id')->nullable()->constrained('purchase_payments')->nullOnDelete();
            $table->decimal('amount', 15, 4);
            $table->timestamps();
            $table->unique(['provider_payment_order_id', 'purchase_id'], 'payment_order_purchase_unique');
        });

        Schema::table('cash_sheet_movements', function (Blueprint $table) {
            $table->foreignId('provider_payment_order_id')->nullable()->after('customer_receipt_payment_id')
                ->constrained('provider_payment_orders')->nullOnDelete();
            $table->foreignId('reversal_of_movement_id')->nullable()->after('provider_payment_order_id')
                ->constrained('cash_sheet_movements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_sheet_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversal_of_movement_id');
            $table->dropConstrainedForeignId('provider_payment_order_id');
        });
        Schema::dropIfExists('provider_payment_order_applications');
        Schema::dropIfExists('provider_payment_orders');
    }
};
