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
        Schema::create('cash_sheets', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('external_id')->nullable()->index();

        $table->foreignId('cash_box_id')
            ->nullable()
            ->constrained('cash_boxes')
            ->nullOnDelete();

        $table->string('cash_box_name')->nullable();

        $table->integer('number')->nullable()->index();

        $table->string('pos_name')->nullable();

        $table->string('cashier_name')->nullable();

        $table->dateTime('opening_date')->nullable();
        $table->dateTime('closing_date')->nullable();

        $table->string('status_name')->nullable();

        $table->text('opening_observation')->nullable();
        $table->text('closing_observation')->nullable();

        $table->string('branch_name')->nullable();
        $table->string('warehouse_name')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_sheets');
    }
};
