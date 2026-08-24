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
        // Crea colores con codigo hexadecimal, orden web y estado.
        Schema::create('colors', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('external_id')->nullable()->index();
        $table->string('name');
        $table->string('hex_code')->nullable();
        $table->integer('web_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colors');
    }
};
