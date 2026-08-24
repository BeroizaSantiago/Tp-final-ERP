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
        // Crea promociones de venta con vigencia, alcance y tipo de descuento.
        Schema::create('sales_promotions', function (Blueprint $table) {
        $table->id();

        $table->string('name');

        $table->date('date_from')->nullable();
        $table->date('date_to')->nullable();

        $table->string('currency_name')->nullable();

        $table->string('applies_to')->nullable();
        // product, category, brand, all

        $table->string('discount_type')->nullable();
        // percentage, fixed_amount, special_price, combo

        $table->decimal('discount_value', 15, 4)->default(0);

        $table->boolean('is_active')->default(true);

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_promotions');
    }
};
