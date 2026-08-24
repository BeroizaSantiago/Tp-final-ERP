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
    Schema::create('stock_adjustments', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('external_id')->nullable()->index();

        $table->string('number')->nullable()->index();

        $table->string('warehouse_name')->nullable();
        $table->string('branch_name')->nullable();

        $table->string('created_by')->nullable();

        $table->decimal('quantity', 15, 4)->default(0);

        $table->dateTime('date')->nullable();

        $table->boolean('is_active')->default(true);

        $table->string('adjustment_stock_reason')->nullable();

        $table->text('notes')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
