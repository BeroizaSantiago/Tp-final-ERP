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
    Schema::create('product_variants', function (Blueprint $table) {
        $table->id();

        $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

        $table->foreignId('size_id')->nullable()->constrained('sizes')->nullOnDelete();
        $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();

        $table->string('sku')->nullable();
        $table->string('bar_code')->nullable();
        $table->string('image_url')->nullable();

        $table->decimal('price_a_with_tax', 15, 4)->nullable();

        $table->decimal('current_stock', 15, 4)->default(0);
        $table->decimal('available_stock', 15, 4)->default(0);

        $table->boolean('is_active')->default(true);

        $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
