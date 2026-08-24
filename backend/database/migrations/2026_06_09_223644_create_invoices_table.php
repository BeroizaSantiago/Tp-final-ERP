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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('external_id')->nullable()->index();

            $table->dateTime('issue_date')->nullable();

            $table->string('receipt_type_name')->nullable();
            $table->integer('second_number')->nullable();
            $table->string('full_number')->nullable()->index();

            $table->unsignedBigInteger('customer_external_id')->nullable()->index();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();

            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone_number')->nullable();

            $table->string('currency_name')->nullable();

            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('taxed_amount', 15, 4)->default(0);
            $table->decimal('non_taxed_amount', 15, 4)->default(0);
            $table->decimal('exempt_amount', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);

            $table->string('status_name')->nullable();
            $table->integer('status_id')->nullable();

            $table->boolean('is_credit')->default(false);

            $table->integer('service_channel_id')->nullable();
            $table->integer('pos_type_id')->nullable();

            $table->string('payment_condition_name')->nullable();

            $table->boolean('printed')->default(false);

            $table->string('authorization_code')->nullable();
            $table->string('mode')->nullable();

            $table->string('related_document_full_name')->nullable();
            $table->integer('count_associate_document')->default(0);

            $table->string('updated_by')->nullable();
            $table->string('seller_full_name')->nullable();
            $table->string('user_name')->nullable();

            $table->decimal('gross_income_perception_amount', 15, 4)->default(0);
            $table->decimal('gross_income_perception_percentage', 8, 4)->default(0);
            $table->unsignedBigInteger('gross_income_tax_id')->nullable();

            $table->decimal('global_discount_percentage', 8, 4)->default(0);
            $table->decimal('net_global_discount_amount', 15, 4)->default(0);
            $table->decimal('tax_global_discount_amount', 15, 4)->default(0);

            $table->decimal('net_total_discount_product', 15, 4)->default(0);
            $table->decimal('tax_total_discount_product', 15, 4)->default(0);

            $table->decimal('net_recharge_amount', 15, 4)->default(0);
            $table->decimal('tax_recharge_amount', 15, 4)->default(0);

            $table->decimal('balance', 15, 2)->default(0);

            $table->string('ecommerce_number')->nullable();
            $table->string('ecommerce_shipping_method')->nullable();
            $table->string('ecommerce_payment_method')->nullable();
            $table->string('ecommerce_payment_method_card')->nullable();

            $table->string('service_channel')->nullable();

            $table->string('color_status_code')->nullable();
            $table->string('color_status_description')->nullable();

            $table->boolean('is_bill_without_delivery')->default(false);
            $table->boolean('is_print_ticket_for_change')->default(false);

            $table->unsignedBigInteger('related_document_credit_note_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
