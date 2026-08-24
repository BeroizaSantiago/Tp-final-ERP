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
    // Crea gastos varios con proveedor/tipo opcionales y desglose de importes.
    Schema::create('misc_expenses', function (Blueprint $table) {
        $table->id();

        $table->foreignId('provider_id')
            ->nullable()
            ->constrained('providers')
            ->nullOnDelete();

        $table->foreignId('expense_type_id')
            ->nullable()
            ->constrained('expense_types')
            ->nullOnDelete();

        $table->date('issue_date');

        $table->string('receipt_type_name')->nullable();
        $table->string('receipt_number')->nullable()->index();

        $table->string('provider_name')->nullable();
        $table->string('branch_name')->nullable();

        $table->decimal('net_amount', 15, 4)->default(0);
        $table->decimal('discount_amount', 15, 4)->default(0);
        $table->decimal('surcharge_amount', 15, 4)->default(0);
        $table->decimal('tax_amount', 15, 4)->default(0);
        $table->decimal('exempt_amount', 15, 4)->default(0);
        $table->decimal('non_taxed_amount', 15, 4)->default(0);
        $table->decimal('perception_amount', 15, 4)->default(0);
        $table->decimal('total_amount', 15, 4)->default(0);

        $table->string('status_name')->default('Registrado');
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
        Schema::dropIfExists('misc_expenses');
    }
};
