<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_receipt_applications', function (Blueprint $table) {

            $table->id();

            $table->foreignId('customer_receipt_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('invoice_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('amount',15,2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_receipt_applications');
    }
};