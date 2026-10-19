<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega los alcances adicionales que permite la vista de "Promociones y
     * Descuentos" de ventas: autores, colecciones y editoriales, sumados a los
     * ya existentes (productos, categorias y marcas).
     */
    public function up(): void
    {
        Schema::table('sales_promotion_items', function (Blueprint $table) {
            $table->foreignId('author_id')
                ->nullable()
                ->constrained('authors')
                ->nullOnDelete()
                ->after('brand_id');

            $table->foreignId('collection_id')
                ->nullable()
                ->constrained('collections')
                ->nullOnDelete()
                ->after('author_id');

            $table->foreignId('publisher_id')
                ->nullable()
                ->constrained('publishers')
                ->nullOnDelete()
                ->after('collection_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_promotion_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
            $table->dropConstrainedForeignId('collection_id');
            $table->dropConstrainedForeignId('publisher_id');
        });
    }
};
