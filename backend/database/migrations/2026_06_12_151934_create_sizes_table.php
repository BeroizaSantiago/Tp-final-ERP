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
        // Crea talles y los vincula opcionalmente a un tipo de talle.
        Schema::create('sizes', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('external_id')->nullable()->index();

        $table->string('name');

        $table->string('external_code')->nullable();

        $table->foreignId('size_type_id')
            ->nullable()
            ->constrained('size_types')
            ->nullOnDelete();

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
        Schema::dropIfExists('sizes');
    }
};
