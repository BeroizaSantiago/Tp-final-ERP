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
    Schema::create('providers', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('external_id')->nullable()->index();
        $table->integer('code')->nullable()->index();

        $table->string('name');
        $table->string('fantasy_name')->nullable();

        $table->string('document_type')->nullable();
        $table->string('identification_number')->nullable()->index();

        $table->string('address')->nullable();
        $table->string('city_name')->nullable();

        $table->string('primary_phone')->nullable();

        $table->boolean('is_active')->default(true);

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
