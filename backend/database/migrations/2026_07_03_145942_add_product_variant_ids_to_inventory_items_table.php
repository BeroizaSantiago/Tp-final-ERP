<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('inventory_items', function (Blueprint $table) {
        $table->foreignId('product_id')
            ->nullable()
            ->after('id')
            ->constrained('products')
            ->nullOnDelete();

        $table->foreignId('product_variant_id')
            ->nullable()
            ->after('product_id')
            ->constrained('product_variants')
            ->nullOnDelete();
    });
}

public function down()
{
    Schema::table('inventory_items', function (Blueprint $table) {
        $table->dropConstrainedForeignId('product_variant_id');
        $table->dropConstrainedForeignId('product_id');
    });
}
};
