<?php

namespace App\Http\Controllers\Api\Purchases\Orders;

use App\Http\Controllers\Controller;
use App\Models\Products\Product;
use App\Models\Purchases\Provider;
use App\Models\Purchases\PurchaseOrder;
use App\Models\Purchases\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Gestiona ordenes de compra a proveedores.
 *
 * Registra la cabecera de la orden, calcula el total de sus items y guarda
 * los productos solicitados dentro de una transaccion.
 */
class PurchaseOrderController extends Controller
{
    public function index()
    {
        return PurchaseOrder::with('provider')
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(20);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return $purchaseOrder->load('provider', 'items.product');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'provider_id' => ['required', 'exists:providers,id'],
            'issue_date' => ['required', 'date'],
            'order_number' => ['required', 'string', 'unique:purchase_orders,order_number'],
            'currency_name' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($data) {
            $provider = Provider::findOrFail($data['provider_id']);

            $total = 0;

            foreach ($data['items'] as $item) {
                $total += $item['quantity'] * $item['unit_price'];
            }

            $order = PurchaseOrder::create([
                'provider_id' => $provider->id,
                'provider_name' => $provider->name,
                'issue_date' => $data['issue_date'],
                'order_number' => $data['order_number'],
                'currency_name' => $data['currency_name'] ?? 'Pesos',
                'total_amount' => $total,
                'status_name' => 'Pendiente',
                'status_id' => 1,
                'created_by' => 'system',
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_amount' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $order->load('provider', 'items.product');
        });
    }
}
