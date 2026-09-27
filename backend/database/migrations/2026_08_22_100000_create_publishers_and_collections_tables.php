<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogos editoriales del catalogo de libros.
 *
 * El ERP tenia marcas y modelos, pero para un catalogo de libros hacen falta
 * tambien la editorial (la casa que edita) y la coleccion (la serie a la que
 * pertenece la obra), que son datos distintos y no conviene mezclarlos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publishers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->nullable()->index();
            $table->string('name');
            $table->string('external_code')->nullable();
            $table->string('country')->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->nullable()->index();

            // La coleccion suele pertenecer a una editorial concreta.
            $table->foreignId('publisher_id')
                ->nullable()
                ->constrained('publishers')
                ->nullOnDelete();

            $table->string('name');
            $table->string('external_code')->nullable();
            $table->integer('web_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('publisher_id')
                ->nullable()
                ->after('brand_id')
                ->constrained('publishers')
                ->nullOnDelete();

            $table->foreignId('collection_id')
                ->nullable()
                ->after('product_model_id')
                ->constrained('collections')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('publisher_id');
            $table->dropConstrainedForeignId('collection_id');
        });

        Schema::dropIfExists('collections');
        Schema::dropIfExists('publishers');
    }
};
