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
        // Agrega al producto relaciones normalizadas con categoria, marca y modelo.
        Schema::table('products', function (Blueprint $table) {

        $table->foreignId('category_id')
            ->nullable()
            ->constrained('product_categories')
            ->nullOnDelete();

        $table->foreignId('brand_id')
            ->nullable()
            ->constrained('brands')
            ->nullOnDelete();

        $table->foreignId('product_model_id')
            ->nullable()
            ->constrained('product_models')
            ->nullOnDelete();

        $table->foreignId('size_id')
            ->nullable()
            ->constrained('sizes')
            ->nullOnDelete();

        $table->foreignId('color_id')
            ->nullable()
            ->constrained('colors')
            ->nullOnDelete();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
