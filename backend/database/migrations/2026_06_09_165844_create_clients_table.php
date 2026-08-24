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
    Schema::create('clients', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('external_id')->nullable()->index();
        $table->string('code')->nullable()->index();

        $table->string('name');
        $table->date('birth_date')->nullable();

        $table->string('document_type')->nullable();
        $table->string('document_number')->nullable()->index();

        $table->string('vat_classification')->nullable();
        $table->string('gross_income_classification')->nullable();

        $table->string('fantasy_name')->nullable();
        $table->string('state_tax_number')->nullable();

        $table->string('payment_condition')->nullable();
        $table->string('payment_condition_detail')->nullable();

        $table->string('currency')->nullable();
        $table->string('price_type')->nullable();

        $table->decimal('discount', 10, 2)->default(0);
        $table->decimal('credit_limit', 15, 2)->nullable();

        $table->string('referred')->nullable();
        $table->string('seller')->nullable();

        $table->string('status')->nullable();

        $table->date('last_payment_date')->nullable();

        $table->string('related_provider')->nullable();

        $table->boolean('is_wholesaler')->default(false);
        $table->boolean('use_credit_invoice')->default(false);

        $table->string('first_phone')->nullable();
        $table->string('second_phone')->nullable();
        $table->string('email')->nullable();

        $table->string('address')->nullable();
        $table->string('address_number')->nullable();
        $table->string('apartment')->nullable();
        $table->string('neighborhood')->nullable();
        $table->string('zip_code')->nullable();

        $table->string('city')->nullable();
        $table->string('state')->nullable();
        $table->string('country')->nullable();

        $table->string('branch_origin')->nullable();

        $table->text('notes')->nullable();

        $table->boolean('is_active')->default(true);

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
