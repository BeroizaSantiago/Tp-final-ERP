<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'position']);
        });

        DB::table('products')
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->orderBy('id')
            ->eachById(function ($product) {
                DB::table('product_images')->insert([
                    'product_id' => $product->id,
                    'path' => $product->image_url,
                    'position' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
