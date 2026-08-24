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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('receipt_types_prefix')->nullable()->index();
            $table->string('receipt_types_fiscal_code')->nullable();

            $table->unsignedBigInteger('related_document_id')->nullable();
            $table->string('related_document_full_number')->nullable();

            $table->dateTime('authorization_code_due_date')->nullable();
            $table->dateTime('payment_due_date')->nullable();

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('currency_id')->nullable();
            $table->unsignedBigInteger('price_type_id')->nullable();
            $table->unsignedBigInteger('origin_type_id')->nullable();
            $table->unsignedBigInteger('receipt_type_id')->nullable();
            $table->unsignedBigInteger('point_of_sale_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();

            $table->string('first_number')->nullable();
            $table->string('letter')->nullable();

            $table->decimal('exchange_rate', 15, 4)->default(1);

            $table->boolean('is_manual')->default(false);
            $table->boolean('print_ticket_for_change')->default(false);
            $table->boolean('is_managed_by_order')->default(false);

            $table->unsignedBigInteger('managed_order_id')->nullable();
            $table->unsignedBigInteger('sale_receipt_could_store_id')->nullable();
            $table->unsignedBigInteger('could_store_id')->nullable();

            $table->unsignedBigInteger('opened_cash_form_id')->nullable();
            $table->unsignedBigInteger('cash_box_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'receipt_types_prefix',
                'receipt_types_fiscal_code',
                'related_document_id',
                'related_document_full_number',
                'authorization_code_due_date',
                'payment_due_date',
                'notes',
                'currency_id',
                'price_type_id',
                'origin_type_id',
                'receipt_type_id',
                'point_of_sale_id',
                'warehouse_id',
                'first_number',
                'letter',
                'exchange_rate',
                'is_manual',
                'print_ticket_for_change',
                'is_managed_by_order',
                'managed_order_id',
                'sale_receipt_could_store_id',
                'could_store_id',
                'opened_cash_form_id',
                'cash_box_id',
            ]);
        });
    }
};
