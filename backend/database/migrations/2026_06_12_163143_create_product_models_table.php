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
        // Crea modelos de producto y su relacion opcional con marcas.
        Schema::create('product_models', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('external_id')
            ->nullable()
            ->index();

        $table->foreignId('brand_id')
            ->nullable()
            ->constrained('brands')
            ->nullOnDelete();

        $table->string('name');

        $table->string('external_code')
            ->nullable();

        $table->boolean('is_active')
            ->default(true);

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_models');
    }
};
