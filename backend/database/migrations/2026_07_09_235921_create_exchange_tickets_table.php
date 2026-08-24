<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_tickets', function (Blueprint $table) {

            $table->id();

            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();

            $table->string('ticket_number')->unique();

            $table->boolean('used')->default(false);

            $table->timestamp('used_at')->nullable();

            $table->foreignId('used_invoice_id')->nullable()->constrained('invoices');

            $table->date('expiration_date')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_tickets');
    }
};