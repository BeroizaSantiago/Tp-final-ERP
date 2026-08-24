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
        // Crea marcas comerciales asociables a productos.
        Schema::create('brands', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('external_id')->nullable()->index();
        $table->string('name');
        $table->string('external_code')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
