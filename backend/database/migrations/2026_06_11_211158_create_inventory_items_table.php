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
    Schema::create('inventory_items', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('product_external_id')->nullable()->index();
        $table->unsignedBigInteger('product_variant_external_id')->nullable()->index();

        $table->integer('code')->nullable()->index();
        $table->string('bar_code')->nullable()->index();
        $table->string('reference_code')->nullable()->index();
        $table->string('company_configuration_code')->nullable();

        $table->string('product_name');

        $table->string('branch_name')->nullable();
        $table->string('warehouse_name')->nullable();

        $table->decimal('min_stock', 15, 4)->default(0);
        $table->decimal('reposition_stock', 15, 4)->default(0);
        $table->decimal('current_stock', 15, 4)->default(0);

        $table->unsignedBigInteger('company_id')->nullable();
        $table->unsignedBigInteger('account_id')->nullable();

        $table->string('color_name')->nullable();
        $table->string('size_name')->nullable();

        $table->string('stock_batch')->nullable();

        $table->decimal('valued_item', 15, 4)->default(0);

        $table->unsignedBigInteger('currency_id')->nullable();
        $table->string('currency_symbol')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
