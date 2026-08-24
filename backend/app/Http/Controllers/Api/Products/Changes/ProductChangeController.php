<?php

namespace App\Http\Controllers\Api\Products\Changes;

use App\Http\Controllers\Controller;
use App\Models\Clients\Client;
use App\Models\Products\Product;
use App\Models\Products\ProductChange;
use App\Models\Products\ProductChangeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Gestiona cambios de productos realizados por clientes.
 *
 * Registra los articulos devueltos y entregados dentro de una transaccion,
 * aumenta el stock de las devoluciones y descuenta el de los productos que
 * reemplazan a los originales.
 */
class ProductChangeController extends Controller
{
    public function index()
    {
        return ProductChange::with('items')
            ->latest()
            ->paginate(20);
    }

    public function show(ProductChange $productChange)
    {
        return $productChange->load(
            'client',
            'items.refundProduct',
            'items.deliveredProduct'
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'reason_type_name' => ['required', 'string'],

            'items' => ['required', 'array', 'min:1'],

            'items.*.refund_product_id' => [
                'required',
                'exists:products,id'
            ],

            'items.*.delivered_product_id' => [
                'required',
                'exists:products,id'
            ],

            'items.*.refund_quantity' => [
                'required',
                'numeric',
                'min:0.01'
            ],

            'items.*.delivered_quantity' => [
                'required',
                'numeric',
                'min:0.01'
            ],

            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($data) {

            $client = Client::findOrFail($data['client_id']);

            $change = ProductChange::create([
                'client_id' => $client->id,
                'customer_name' => $client->name,
                'reason_type_name' => $data['reason_type_name'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {

                $refundProduct = Product::findOrFail(
                    $item['refund_product_id']
                );

                $deliveredProduct = Product::findOrFail(
                    $item['delivered_product_id']
                );

                ProductChangeItem::create([
                    'product_change_id' => $change->id,

                    'refund_product_id' => $refundProduct->id,
                    'delivered_product_id' => $deliveredProduct->id,

                    'refund_quantity' => $item['refund_quantity'],
                    'delivered_quantity' => $item['delivered_quantity'],
                ]);

                // DEVUELTO → vuelve stock
                $refundProduct->increment(
                    'current_stock',
                    $item['refund_quantity']
                );

                $refundProduct->increment(
                    'available_stock',
                    $item['refund_quantity']
                );

                // ENTREGADO → sale stock
                $deliveredProduct->decrement(
                    'current_stock',
                    $item['delivered_quantity']
                );

                $deliveredProduct->decrement(
                    'available_stock',
                    $item['delivered_quantity']
                );
            }

            return $change->load(
                'items.refundProduct',
                'items.deliveredProduct'
            );
        });
    }
}
