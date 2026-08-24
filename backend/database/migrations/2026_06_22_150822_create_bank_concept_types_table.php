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
    Schema::create('bank_concept_types', function (Blueprint $table) {
        $table->id();

        $table->string('name');

        $table->string('movement_type')->nullable();
        // INGRESO / EGRESO

        $table->string('currency_name')->nullable();

        $table->unsignedBigInteger('gl_account_id')->nullable();
        $table->string('gl_account_name')->nullable();

        $table->boolean('is_active')->default(true);

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_concept_types');
    }
};
