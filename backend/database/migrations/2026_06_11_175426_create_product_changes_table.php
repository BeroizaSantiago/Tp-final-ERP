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
    Schema::create('product_changes', function (Blueprint $table) {
        $table->id();

        $table->foreignId('client_id')
            ->nullable()
            ->constrained('clients')
            ->nullOnDelete();

        $table->string('customer_name');

        $table->string('reason_type_name')->nullable();

        $table->text('notes')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_changes');
    }
};
