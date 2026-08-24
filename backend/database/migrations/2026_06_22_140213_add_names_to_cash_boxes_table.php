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
    Schema::table('cash_boxes', function (Blueprint $table) {
        $table->string('box_type_name')->nullable();
        $table->string('branch_name')->nullable();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_boxes', function (Blueprint $table) {
            //
        });
    }
};
