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
    Schema::dropIfExists('product_author');
}

public function down(): void
{
    Schema::create('product_author', function (Blueprint $table) {
        $table->foreignId('product_id')
            ->constrained('products')
            ->cascadeOnDelete();

        $table->foreignId('author_id')
            ->constrained('authors')
            ->cascadeOnDelete();

        $table->unique(['product_id', 'author_id']);
    });
}
};
