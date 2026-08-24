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
        // Crea comprobantes de compra recibidos de proveedores.
        Schema::create('purchase_receipts', function (Blueprint $table) {
        $table->id();

        $table->foreignId('provider_id')
            ->nullable()
            ->constrained('providers')
            ->nullOnDelete();

        $table->dateTime('issue_date')->nullable();

        $table->string('receipt_type_name')->nullable();
        $table->string('receipt_number')->nullable()->index();

        $table->string('provider_name')->nullable();

        $table->string('currency_name')->nullable();

        $table->decimal('total_amount', 15, 4)->default(0);

        $table->string('status_name')->nullable();
        $table->integer('status_id')->nullable();

        $table->string('created_by')->nullable();

        $table->text('notes')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_receipts');
    }
};
