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
    Schema::create('bank_accounts', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('external_id')->nullable()->index();

        $table->foreignId('bank_id')
            ->nullable()
            ->constrained('banks')
            ->nullOnDelete();

        $table->foreignId('bank_branch_id')
            ->nullable()
            ->constrained('bank_branches')
            ->nullOnDelete();

        $table->string('bank_name')->nullable();
        $table->string('bank_branch_name')->nullable();

        $table->string('account_number')->nullable()->index();

        $table->string('bank_account_type_text')->nullable();

        $table->string('currency_name')->nullable();

        $table->string('branch_name')->nullable();

        $table->decimal('amount_balance', 15, 4)->default(0);

        $table->boolean('is_active')->default(true);

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
