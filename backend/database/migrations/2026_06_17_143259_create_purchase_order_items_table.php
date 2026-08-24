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
        // Crea los items solicitados dentro de una orden de compra.
        Schema::create('purchase_order_items', function (Blueprint $table) {
        $table->id();

        $table->foreignId('purchase_order_id')
            ->constrained()
            ->cascadeOnDelete();

        $table->foreignId('product_id')
            ->nullable()
            ->constrained('products')
            ->nullOnDelete();

        $table->string('description');

        $table->decimal('quantity', 15, 4);

        $table->decimal('unit_price', 15, 4)
            ->default(0);

        $table->decimal('total_amount', 15, 4)
            ->default(0);

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
