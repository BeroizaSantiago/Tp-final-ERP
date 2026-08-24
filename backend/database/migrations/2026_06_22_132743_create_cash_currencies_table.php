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
    Schema::create('cash_currencies', function (Blueprint $table) {
        $table->id();

        $table->foreignId('cash_box_id')
            ->constrained('cash_boxes')
            ->cascadeOnDelete();

        $table->unsignedBigInteger('external_id')->nullable()->index();

        $table->unsignedBigInteger('currency_id')->nullable();
        $table->string('currency_name')->nullable();

        $table->unsignedBigInteger('gl_account_id')->nullable();

        $table->decimal('last_closing_balance', 15, 4)->default(0);

        $table->boolean('last_is_manual')->default(false);
        $table->boolean('is_active')->default(true);
        $table->boolean('deleted')->default(false);

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_currencies');
    }
};
