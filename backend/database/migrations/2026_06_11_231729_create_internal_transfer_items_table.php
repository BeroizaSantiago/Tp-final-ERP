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
    Schema::create('internal_transfer_items', function (Blueprint $table) {
        $table->id();

        $table->foreignId('internal_transfer_id')
            ->constrained()
            ->cascadeOnDelete();

        $table->foreignId('inventory_item_id')
            ->constrained()
            ->cascadeOnDelete();

        $table->decimal('quantity', 15, 4);

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_transfer_items');
    }
};
