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
    Schema::create('cash_boxes', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('external_id')->nullable()->index();

        $table->string('code')->nullable();
        $table->string('name');

        $table->unsignedBigInteger('branch_id')->nullable();
        $table->unsignedBigInteger('warehouse_id')->nullable();
        $table->unsignedBigInteger('box_type_id')->nullable();
        $table->unsignedBigInteger('status_id')->nullable();
        $table->unsignedBigInteger('treasury_id')->nullable();

        $table->boolean('is_active')->default(true);

        $table->unsignedBigInteger('last_closing_cash_form_id')->nullable();

        $table->string('status_description')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_boxes');
    }
};
