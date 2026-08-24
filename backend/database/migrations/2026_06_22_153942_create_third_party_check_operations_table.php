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
    Schema::create('third_party_check_operations', function (Blueprint $table) {
        $table->id();

        $table->date('operation_date');

        $table->string('number')->nullable();

        $table->string('operation_type');

        $table->integer('checks_count')->default(0);

        $table->decimal('amount', 15, 4)->default(0);

        $table->string('operator_name')->nullable();

        $table->string('created_by')->nullable();

        $table->text('notes')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('third_party_check_operations');
    }
};
