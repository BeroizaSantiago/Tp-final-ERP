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
    Schema::create('product_change_items', function (Blueprint $table) {
        $table->id();

        $table->foreignId('product_change_id')
            ->constrained()
            ->cascadeOnDelete();

        $table->foreignId('refund_product_id')
            ->nullable()
            ->constrained('products')
            ->nullOnDelete();

        $table->foreignId('delivered_product_id')
            ->nullable()
            ->constrained('products')
            ->nullOnDelete();

        $table->decimal('refund_quantity', 10, 2)->default(1);
        $table->decimal('delivered_quantity', 10, 2)->default(1);

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_change_items');
    }
};
