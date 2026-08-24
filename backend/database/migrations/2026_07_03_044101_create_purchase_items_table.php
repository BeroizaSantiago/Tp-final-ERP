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
Schema::create('purchase_items', function (Blueprint $table) {
    $table->id();

    $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();

    $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
    $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

    $table->string('product_code')->nullable();
    $table->string('product_name')->nullable();

    $table->foreignId('size_id')->nullable()->constrained('sizes')->nullOnDelete();
    $table->string('size_name')->nullable();

    $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();
    $table->string('color_name')->nullable();

    $table->decimal('quantity', 15, 4)->default(0);
    $table->decimal('unit_price', 15, 4)->default(0);
    $table->decimal('discount_percentage', 8, 4)->default(0);
    $table->decimal('tax_percentage', 8, 4)->default(21);

    $table->decimal('subtotal_amount', 15, 4)->default(0);
    $table->decimal('tax_amount', 15, 4)->default(0);
    $table->decimal('total_amount', 15, 4)->default(0);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
