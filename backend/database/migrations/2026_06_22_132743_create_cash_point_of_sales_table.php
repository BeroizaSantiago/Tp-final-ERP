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
    Schema::create('cash_point_of_sales', function (Blueprint $table) {
        $table->id();

        $table->foreignId('cash_box_id')
            ->constrained('cash_boxes')
            ->cascadeOnDelete();
        
        $table->unsignedBigInteger('external_id')->nullable()->index();
        $table->unsignedBigInteger('cash_point_of_sale_id')->nullable();

        $table->string('number')->nullable();

        $table->boolean('is_manual')->default(false);

        $table->unsignedBigInteger('pos_type_id')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_point_of_sales');
    }
};
