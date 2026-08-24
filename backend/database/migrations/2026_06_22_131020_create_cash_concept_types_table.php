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
    Schema::create('cash_concept_types', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('external_id')
            ->nullable();

        $table->string('name');

        $table->unsignedBigInteger('gl_account_id')
            ->nullable();

        $table->string('gl_account_name')
            ->nullable();

        $table->integer('movement_type_id')
            ->nullable();

        $table->string('movement_type_name')
            ->nullable();

        $table->boolean('is_active')
            ->default(true);

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_concept_types');
    }
};
