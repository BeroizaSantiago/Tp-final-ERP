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
        // Crea ordenes de compra emitidas a proveedores.
        Schema::create('purchase_orders', function (Blueprint $table) {
        $table->id();

        $table->foreignId('provider_id')
            ->nullable()
            ->constrained('providers')
            ->nullOnDelete();

        $table->date('issue_date');

        $table->string('order_number')->unique();

        $table->string('provider_name')->nullable();

        $table->string('currency_name')
            ->default('Pesos');

        $table->decimal('total_amount', 15, 4)
            ->default(0);

        $table->string('status_name')
            ->default('Pendiente');

        $table->integer('status_id')
            ->default(1);

        $table->string('created_by')
            ->nullable();

        $table->text('notes')
            ->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
