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
    Schema::create('invoice_items', function (Blueprint $table) {
        $table->id();

        $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();

        $table->unsignedBigInteger('external_id')->nullable()->index();

        $table->string('product_search_code')->nullable();

        $table->unsignedBigInteger('product_external_id')->nullable()->index();
        $table->unsignedBigInteger('product_variant_external_id')->nullable()->index();

        $table->unsignedBigInteger('size_id')->nullable();
        $table->string('size_name')->nullable();

        $table->unsignedBigInteger('color_id')->nullable();
        $table->string('color_name')->nullable();

        $table->string('description')->nullable();

        $table->decimal('quantity', 15, 4)->default(0);
        $table->decimal('pending', 15, 4)->default(0);

        $table->unsignedBigInteger('tax_aliquot_id')->nullable();
        $table->decimal('tax_aliquot_percentage', 8, 4)->default(0);

        $table->decimal('unit_price_with_taxes', 15, 4)->default(0);
        $table->decimal('unit_price', 15, 4)->default(0);

        $table->decimal('discount_percentage', 8, 4)->default(0);
        $table->decimal('discount_amount', 15, 4)->default(0);

        $table->decimal('subtotal_amount', 15, 4)->default(0);
        $table->decimal('tax_amount', 15, 4)->default(0);
        $table->decimal('total_amount', 15, 4)->default(0);

        $table->unsignedBigInteger('warehouse_id')->nullable();

        $table->text('notes')->nullable();

        $table->boolean('is_promotion')->default(false);
        $table->unsignedBigInteger('sales_promotion_id')->nullable();

        $table->string('product_name')->nullable();
        $table->string('product_code')->nullable();
        $table->string('product_barcode')->nullable();
        $table->string('product_reference_code')->nullable();
        $table->string('product_display_text')->nullable();

        $table->unsignedBigInteger('product_category_id')->nullable();
        $table->string('product_category_name')->nullable();

        $table->string('brand_name')->nullable();
        $table->string('model_name')->nullable();

        $table->string('variant_barcode')->nullable();
        $table->decimal('variant_price_a', 15, 4)->nullable();
        $table->decimal('variant_price_b', 15, 4)->nullable();
        $table->decimal('variant_price_c', 15, 4)->nullable();
        $table->decimal('variant_price_d', 15, 4)->nullable();

        $table->boolean('is_active')->default(true);

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
