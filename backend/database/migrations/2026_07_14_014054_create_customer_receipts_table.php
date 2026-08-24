<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_receipts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('number')->unique();

            $table->date('receipt_date');

            $table->decimal('total_amount',15,2);

            $table->decimal('discount_amount',15,2)->default(0);

            $table->decimal('surcharge_amount',15,2)->default(0);

            $table->decimal('net_amount',15,2);

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_receipts');
    }
};