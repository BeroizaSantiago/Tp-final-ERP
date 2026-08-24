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
    Schema::create('bank_movements', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('external_id')->nullable()->index();

        $table->foreignId('bank_account_id')
            ->nullable()
            ->constrained('bank_accounts')
            ->nullOnDelete();

        $table->string('bank_account_name')->nullable();

        $table->dateTime('issue_date')->nullable();

        $table->foreignId('bank_concept_type_id')
            ->nullable()
            ->constrained('bank_concept_types')
            ->nullOnDelete();

        $table->string('bank_concept_name')->nullable();

        $table->decimal('amount', 15, 4)->default(0);

        $table->boolean('reconciled')->default(false);

        $table->string('movement_type_name')->nullable();

        $table->string('service_channel_name')->nullable();

        $table->text('legend')->nullable();

        $table->foreignId('bank_account_destination_id')
            ->nullable()
            ->constrained('bank_accounts')
            ->nullOnDelete();

        $table->string('bank_account_destination_name')->nullable();

        $table->boolean('is_automatic')->default(false);

        $table->string('voucher')->nullable();

        $table->dateTime('council_date')->nullable();

        $table->unsignedBigInteger('import_process_id')->nullable();

        $table->string('branch_name')->nullable();

        $table->dateTime('creation_date')->nullable();
        $table->string('created_by')->nullable();

        $table->dateTime('updated_date')->nullable();
        $table->string('updated_by')->nullable();

        $table->dateTime('deleted_date')->nullable();
        $table->string('deleted_by')->nullable();

        $table->boolean('is_active')->default(true);

        $table->string('mode')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_movements');
    }
};
