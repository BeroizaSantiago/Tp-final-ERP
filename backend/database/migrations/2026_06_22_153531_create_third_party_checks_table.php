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
    Schema::create('third_party_checks', function (Blueprint $table) {
        $table->id();

        $table->date('issue_date')->nullable();
        $table->date('check_date')->nullable();

        $table->foreignId('bank_id')
            ->nullable()
            ->constrained('banks')
            ->nullOnDelete();

        $table->string('bank_name')->nullable();

        $table->string('check_number')->nullable()->index();

        $table->decimal('amount', 15, 4)->default(0);

        $table->string('endorser')->nullable();

        $table->boolean('is_electronic')->default(false);

        $table->string('echeq_number')->nullable();

        $table->string('status_name')->default('Pendiente');

        $table->date('charged_date')->nullable();
        $table->date('deposited_date')->nullable();

        $table->dateTime('creation_date')->nullable();

        $table->string('cuit')->nullable();

        $table->string('drawer')->nullable();

        $table->text('notes')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('third_party_checks');
    }
};
