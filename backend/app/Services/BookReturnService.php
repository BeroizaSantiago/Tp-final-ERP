<?php

namespace App\Services;

use App\Models\BookReturn;
use App\Models\Products\Product;
use App\Models\Clients\Client;
use App\Models\Purchases\Provider;
use Illuminate\Support\Str;

/**
 * Servicio para la lógica de negocio de devoluciones de libros.
 */
class BookReturnService
{
    /**
     * Crear una nueva devolución de libro.
     *
     * @param array $data Datos validados de la devolución
     * @return BookReturn
     */
    public function create(array $data): BookReturn
    {
        return BookReturn::create([
            'return_type' => $data['return_type'],
            'returnable_type' => $data['returnable_type'],
            'returnable_id' => $data['returnable_id'],
            'quantity' => $data['quantity'],
            'condition' => $data['condition'] ?? null,
            'reason' => $data['reason'],
            'client_id' => $data['return_type'] === 'client' ? $data['client_id'] : null,
            'provider_id' => $data['return_type'] === 'supplier' ? $data['provider_id'] : null,
            'invoice_id' => $data['invoice_id'],
            'purchase_id' => $data['purchase_id'],
            'status' => 'pending',
            'idempotency_key' => $data['idempotency_key'] ?? Str::uuid()->toString(),
        ]);
    }

    /**
     * Validar que el producto puede ser devuelto.
     *
     * @param BookReturn $return
     * @return bool
     */
    public function canBeReturned(BookReturn $return): bool
    {
        $product = $return->returnable;

        if (! $product) {
            return false;
        }

        // Validar que el producto existe y está activo
        if (! $product->is_active) {
            return false;
        }

        // Validaciones específicas por tipo de devolución
        if ($return->return_type === 'client') {
            // Devolución a cliente: verificar que el cliente existe y está activo
            return $return->client && $return->client->is_active;
        }

        if ($return->return_type === 'supplier') {
            // Devolución a proveedor: verificar que el proveedor existe y está activo
            return $return->provider && $return->provider->is_active;
        }

        return true;
    }

    /**
     * Obtener el título o descripción legible de la devolución.
     *
     * @param BookReturn $return
     * @return string
     */
    public function getDescription(BookReturn $return): string
    {
        $productName = $return->returnable ? $return->returnable->name : 'Producto';
        $type = $return->return_type === 'client' ? 'cliente' : 'proveedor';

        return "Devolución de {$quantity} libros a {$type}: {$productName}";
    }
}