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
        Schema::table('product_variants', function (Blueprint $table) {
            // Agregar columnas para los maestros que definen la variante
            $table->foreignId('category_id')->nullable()->after('product_id')->constrained('product_categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->after('category_id')->constrained('brands')->nullOnDelete();
            $table->foreignId('publisher_id')->nullable()->after('brand_id')->constrained('publishers')->nullOnDelete();
            $table->foreignId('product_model_id')->nullable()->after('publisher_id')->constrained('product_models')->nullOnDelete();
            $table->foreignId('collection_id')->nullable()->after('product_model_id')->constrained('collections')->nullOnDelete();
        });

        // Migrar datos existentes: mover size_id/category_id y color_id/brand_id
        // Si existen datos, los movemos a las nuevas columnas
        DB::statement('UPDATE product_variants SET category_id = (SELECT category_id FROM products WHERE products.id = product_variants.product_id) WHERE category_id IS NULL');
        DB::statement('UPDATE product_variants SET brand_id = (SELECT brand_id FROM products WHERE products.id = product_variants.product_id) WHERE brand_id IS NULL');
        DB::statement('UPDATE product_variants SET publisher_id = (SELECT publisher_id FROM products WHERE products.id = product_variants.product_id) WHERE publisher_id IS NULL');
        DB::statement('UPDATE product_variants SET product_model_id = (SELECT product_model_id FROM products WHERE products.id = product_variants.product_id) WHERE product_model_id IS NULL');
        DB::statement('UPDATE product_variants SET collection_id = (SELECT collection_id FROM products WHERE products.id = product_variants.product_id) WHERE collection_id IS NULL');

        Schema::table('product_variants', function (Blueprint $table) {
            // Eliminar columnas de tamaño y color ya que no aplican a libros
            $table->dropForeign(['size_id']);
            $table->dropColumn('size_id');
            $table->dropForeign(['color_id']);
            $table->dropColumn('color_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreignId('size_id')->nullable()->constrained('sizes')->nullOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
            $table->dropForeign(['brand_id']);
            $table->dropColumn('brand_id');
            $table->dropForeign(['publisher_id']);
            $table->dropColumn('publisher_id');
            $table->dropForeign(['product_model_id']);
            $table->dropColumn('product_model_id');
            $table->dropForeign(['collection_id']);
            $table->dropColumn('collection_id');
        });
    }
};
