<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_ia', function (Blueprint $table) {
            $table->foreign('usuario_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('cliente_id')
                ->references('id')
                ->on('clients')
                ->nullOnDelete();

            $table->foreign('detalle_venta_id')
                ->references('id')
                ->on('invoice_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('request_ia', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->dropForeign(['cliente_id']);
            $table->dropForeign(['detalle_venta_id']);
        });
    }
};
