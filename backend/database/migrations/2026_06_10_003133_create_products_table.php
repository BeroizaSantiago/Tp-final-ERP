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
        Schema::create('products', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('external_id')->nullable()->index();
        $table->string('code')->nullable()->index();

        $table->string('bar_code')->nullable()->index();
        $table->string('reference_code')->nullable()->index();

        $table->string('name');

        $table->string('currency_symbol')->nullable();
        $table->string('currency_name')->nullable();

        $table->string('product_type_name')->nullable();
        $table->string('aliquot_name')->nullable();

        $table->string('brand')->nullable();
        $table->string('model')->nullable();
        $table->string('category')->nullable();

        $table->string('unit_measure_name')->nullable();
        $table->string('size_type_name')->nullable();

        $table->boolean('on_sale')->default(false);
        $table->boolean('can_move_stock')->default(false);
        $table->boolean('allows_negative_stock')->default(false);
        $table->boolean('is_own')->default(false);
        $table->boolean('has_lot')->default(false);
        $table->boolean('is_fractionated')->default(false);

        $table->decimal('cost_without_discount', 15, 4)->nullable();
        $table->decimal('cost_without_discount_with_tax_aliquot', 15, 4)->nullable();

        $table->decimal('discount1', 8, 4)->nullable();
        $table->decimal('discount2', 8, 4)->nullable();
        $table->decimal('discount3', 8, 4)->nullable();

        $table->decimal('cost_with_discount', 15, 4)->nullable();
        $table->decimal('cost_with_discount_with_tax_aliquot', 15, 4)->nullable();

        $table->decimal('bonus_recharge', 8, 4)->nullable();

        $table->decimal('replacement_cost', 15, 4)->nullable();
        $table->decimal('replacement_cost_with_tax_aliquot', 15, 4)->nullable();

        $table->decimal('last_purchase_price', 15, 4)->nullable();
        $table->decimal('last_purchase_price_with_tax', 15, 4)->nullable();

        $table->decimal('price_a', 15, 4)->nullable();
        $table->decimal('price_a_with_tax', 15, 4)->nullable();
        $table->decimal('markup_a', 8, 4)->nullable();

        $table->decimal('price_b', 15, 4)->nullable();
        $table->decimal('price_b_with_tax', 15, 4)->nullable();
        $table->decimal('markup_b', 8, 4)->nullable();

        $table->decimal('price_c', 15, 4)->nullable();
        $table->decimal('price_c_with_tax', 15, 4)->nullable();
        $table->decimal('markup_c', 8, 4)->nullable();

        $table->decimal('price_d', 15, 4)->nullable();
        $table->decimal('price_d_with_tax', 15, 4)->nullable();
        $table->decimal('markup_d', 8, 4)->nullable();

        $table->text('description')->nullable();
        $table->text('notes')->nullable();

        $table->decimal('min_stock', 15, 4)->nullable();
        $table->decimal('reposition_stock', 15, 4)->nullable();
        $table->decimal('purchase_min_amount', 15, 4)->nullable();

        $table->decimal('current_stock', 15, 4)->default(0);
        $table->decimal('available_stock', 15, 4)->default(0);

        $table->string('web_title')->nullable();
        $table->text('web_short_description')->nullable();
        $table->text('web_description')->nullable();

        $table->decimal('weight', 15, 4)->nullable();
        $table->decimal('height', 15, 4)->nullable();
        $table->decimal('width', 15, 4)->nullable();
        $table->decimal('length', 15, 4)->nullable();

        $table->json('extended_info')->nullable();

        $table->boolean('is_web_enabled')->default(false);
        $table->boolean('is_active')->default(true);

        $table->string('principal_provider_name')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
