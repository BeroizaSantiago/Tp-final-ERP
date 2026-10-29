<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_return_purchase_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('book_return_purchase_id')
                ->constrained('book_return_purchases')
                ->cascadeOnDelete();

            $table->foreignId('purchase_receipt_item_id')
                ->constrained('purchase_receipt_items')
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();

            $table->decimal('quantity', 15, 4);
            $table->string('condition', 20)->default('sellable');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('purchase_receipt_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_return_purchase_items');
    }
};