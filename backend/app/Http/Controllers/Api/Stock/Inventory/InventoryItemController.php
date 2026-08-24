<?php

namespace App\Http\Controllers\Api\Stock\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Stock\InventoryItem;
use App\Models\Products\Product;
use App\Models\Products\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controlador de Inventario Ítem.
 *
 * Coordina las solicitudes, validaciones y respuestas del módulo Inventario Ítem del ERP.
 */
class InventoryItemController extends Controller
{
    public function index()
    {
        return InventoryItem::with([
            'product',
            'variant.size',
            'variant.color',
        ])
            ->orderBy('product_name')
            ->paginate(50);
    }

    public function show(InventoryItem $inventoryItem)
    {
        return $inventoryItem->load([
            'product',
            'variant.size',
            'variant.color',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)],
            'product_variant_id' => ['required', 'exists:product_variants,id'],

            'branch_name' => ['nullable', 'string'],
            'warehouse_name' => ['nullable', 'string'],

            'min_stock' => ['nullable', 'numeric'],
            'reposition_stock' => ['nullable', 'numeric'],
            'current_stock' => ['required', 'numeric', 'min:0'],

            'stock_batch' => ['nullable', 'string'],
            'valued_item' => ['nullable', 'numeric'],
            'currency_symbol' => ['nullable', 'string'],
        ], [
            'product_id.required' => 'Seleccioná un producto.',
            'product_id.exists' => 'El producto seleccionado no existe o está inhabilitado.',
            'product_variant_id.required' => 'Seleccioná una variante.',
            'product_variant_id.exists' => 'La variante seleccionada no existe en la base de datos.',
        ]);

        $product = Product::findOrFail($data['product_id']);

        $variant = ProductVariant::with(['size', 'color'])
            ->findOrFail($data['product_variant_id']);

        if ((int) $variant->product_id !== (int) $product->id) {
            return response()->json([
                'message' => 'La variante seleccionada no pertenece al producto.',
            ], 422);
        }

        $inventoryItem = InventoryItem::updateOrCreate(
            [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'warehouse_name' => $data['warehouse_name'] ?? 'DEPÓSITO PRINCIPAL',
            ],
            [
                'product_external_id' => $product->external_id,
                'product_variant_external_id' => $variant->external_id ?? null,

                'code' => $product->code,
                'bar_code' => $product->bar_code,
                'reference_code' => $product->reference_code,

                'product_name' => $product->name,

                'branch_name' => $data['branch_name'] ?? 'SUCURSAL',
                'warehouse_name' => $data['warehouse_name'] ?? 'DEPÓSITO PRINCIPAL',

                'min_stock' => $data['min_stock'] ?? 0,
                'reposition_stock' => $data['reposition_stock'] ?? 0,
                'current_stock' => $data['current_stock'],

                'color_name' => $variant->color->name ?? null,
                'size_name' => $variant->size->name ?? null,

                'stock_batch' => $data['stock_batch'] ?? null,
                'valued_item' => $data['valued_item'] ?? 0,
                'currency_symbol' => $data['currency_symbol'] ?? '$',
            ]
        );

        $variant->update([
            'current_stock' => $data['current_stock'],
            'available_stock' => $data['current_stock'],
        ]);

        app(\App\Services\StockService::class)
            ->recalculateProductStock($product);

        return $inventoryItem->load([
            'product',
            'variant.size',
            'variant.color',
        ]);
    }
}
