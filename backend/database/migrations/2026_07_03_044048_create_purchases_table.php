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
Schema::create('purchases', function (Blueprint $table) {
    $table->id();

    $table->foreignId('provider_id')->nullable()->constrained('providers')->nullOnDelete();

    $table->date('issue_date');
    $table->date('payment_due_date')->nullable();
    $table->date('vat_imputation_date')->nullable();

    $table->string('receipt_type_name')->nullable(); // Factura, Nota Crédito, Ticket, etc.
    $table->string('receipt_types_prefix')->nullable(); // FC, NC, ND, TK
    $table->string('letter')->nullable();
    $table->string('first_number')->nullable();
    $table->string('second_number')->nullable();
    $table->string('full_number')->nullable();

    $table->string('provider_name')->nullable();
    $table->string('currency_name')->default('Pesos');

    $table->decimal('taxed_amount', 15, 4)->default(0);
    $table->decimal('tax_amount', 15, 4)->default(0);
    $table->decimal('non_taxed_amount', 15, 4)->default(0);
    $table->decimal('exempt_amount', 15, 4)->default(0);
    $table->decimal('discount_amount', 15, 4)->default(0);
    $table->decimal('surcharge_amount', 15, 4)->default(0);
    $table->decimal('total_amount', 15, 4)->default(0);
    $table->decimal('balance', 15, 4)->default(0);

    $table->string('status_name')->default('Pendiente');
    $table->integer('status_id')->default(1);

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
        Schema::dropIfExists('purchases');
    }
};
