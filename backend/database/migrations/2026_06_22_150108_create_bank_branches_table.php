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
    Schema::create('bank_branches', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('external_id')->nullable()->index();

        $table->foreignId('bank_id')
            ->nullable()
            ->constrained('banks')
            ->nullOnDelete();

        $table->string('bank_name')->nullable();

        $table->string('name');

        $table->string('code')->nullable();

        $table->string('address')->nullable();

        $table->string('city_name')->nullable();

        $table->boolean('is_active')->default(true);

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_branches');
    }
};
