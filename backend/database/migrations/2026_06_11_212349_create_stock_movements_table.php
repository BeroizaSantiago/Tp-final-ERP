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
    Schema::create('stock_movements', function (Blueprint $table) {
        $table->id();

        $table->foreignId('inventory_item_id')
            ->constrained()
            ->cascadeOnDelete();

        $table->string('movement_type');
        /*
            sale
            credit_note
            debit_note
            adjustment
            internal_transfer
            product_change
            remito
            purchase
        */

        $table->decimal('quantity', 15, 4);

        $table->decimal('stock_before', 15, 4)->default(0);
        $table->decimal('stock_after', 15, 4)->default(0);

        $table->string('reference_type')->nullable();
        $table->unsignedBigInteger('reference_id')->nullable();

        $table->text('notes')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
