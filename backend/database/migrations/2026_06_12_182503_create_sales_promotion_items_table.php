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
        // Crea los alcances especificos de una promocion por producto, categoria o marca.
        Schema::create('sales_promotion_items', function (Blueprint $table) {
        $table->id();

        $table->foreignId('sales_promotion_id')
            ->constrained()
            ->cascadeOnDelete();

        $table->foreignId('product_id')
            ->nullable()
            ->constrained('products')
            ->nullOnDelete();

        $table->foreignId('category_id')
            ->nullable()
            ->constrained('product_categories')
            ->nullOnDelete();

        $table->foreignId('brand_id')
            ->nullable()
            ->constrained('brands')
            ->nullOnDelete();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_promotion_items');
    }
};
