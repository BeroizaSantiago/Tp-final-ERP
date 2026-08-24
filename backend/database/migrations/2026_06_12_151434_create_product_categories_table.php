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
        // Crea categorias de productos con jerarquia, orden web y estado.
        Schema::create('product_categories', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('external_id')->nullable()->index();

        $table->string('name');

        $table->string('external_code')->nullable();

        $table->foreignId('parent_id')
            ->nullable()
            ->constrained('product_categories')
            ->nullOnDelete();

        $table->integer('web_order')->default(0);

        $table->string('image')->nullable();

        $table->boolean('is_active')->default(true);

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
