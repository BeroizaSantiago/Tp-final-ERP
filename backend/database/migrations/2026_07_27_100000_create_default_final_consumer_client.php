<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('clients')
            ->whereRaw('LOWER(name) = ?', ['consumidor final'])
            ->exists();

        if (! $exists) {
            DB::table('clients')->insert([
                'code' => 'CF',
                'name' => 'Consumidor Final',
                'document_type' => 'DNI',
                'document_number' => '0',
                'vat_classification' => 'Consumidor Final',
                'payment_condition' => 'CONTADO',
                'currency' => 'Pesos',
                'price_type' => 'Precio A',
                'discount' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // No elimina clientes porque podría haber sido utilizado en comprobantes.
    }
};
