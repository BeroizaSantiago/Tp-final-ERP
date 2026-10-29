<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('book_returns', function (Blueprint $table) {
            $table->id();
            $table->enum('return_type', ['client', 'supplier'])->comment('Tipo de devolución: cliente o proveedor');
            $table->morphs('returnable'); // productable_type y productable_id (Producto)
            $table->integer('quantity')->default(1)->comment('Cantidad de libros devueltos');
            $table->string('condition')->nullable()->comment('Estado de los libros devueltos (buen estado, dañado, etc.)');
            $table->text('reason')->nullable()->comment('Motivo de la devolución');
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending')->comment('Estado de la devolución');
            $table->foreignId('client_id')->nullable()->comment('Cliente que devuelve (si es devolución a cliente)');
            $table->foreignId('provider_id')->nullable()->comment('Proveedor que recibe la devolución (si es devolcción a proveedor)');
            $table->foreignId('invoice_id')->nullable()->comment('Factura original relacionada');
            $table->foreignId('purchase_id')->nullable()->comment('Compra original relacionada');
            $table->timestamps();

            $table->index(['return_type', 'status']);
            $table->index('returnable_type');
        });
    }

    public function down(): void {
        Schema::dropIfExists('book_returns');
    }
};