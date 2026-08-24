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
    Schema::create('checkbooks', function (Blueprint $table) {
        $table->id();

        $table->foreignId('bank_account_id')
            ->nullable()
            ->constrained('bank_accounts')
            ->nullOnDelete();

        $table->string('bank_name')->nullable();
        $table->string('account_number')->nullable();

        $table->integer('initial_check_number');
        $table->integer('final_check_number');

        $table->integer('available_checks')->default(0);

        $table->boolean('is_echeq')->default(false);

        $table->string('status_name')->default('Activa');

        $table->boolean('is_active')->default(true);

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkbooks');
    }
};
