
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_ia', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('usuario_id')->nullable()->index();
            $table->unsignedBigInteger('cliente_id')->nullable()->index();
            $table->unsignedBigInteger('detalle_venta_id')->nullable()->index();

            $table->string('estado', 30)->default('pendiente');

            $table->text('solicitud');
            $table->longText('respuesta')->nullable();

            $table->string('modelo_ia', 100)->nullable();
            $table->text('mensaje_error')->nullable();

            $table->string('tipo_solicitud', 50)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_ia');
    }
};
