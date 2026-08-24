<?php

namespace App\Http\Controllers\Api\Products\Pricing;

use App\Http\Controllers\Controller;
use App\Models\Products\Product;
use Illuminate\Http\Request;

/**
 * Simula y aplica actualizaciones masivas de precios de productos.
 *
 * Filtra por categoria, marca o producto, calcula el precio nuevo segun un
 * porcentaje de aumento o descuento y opcionalmente persiste el resultado.
 */
class ProductPriceUpdateController extends Controller
{
    public function simulate(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric'],
            'operation' => ['required', 'in:add,subtract'],
            'price_field' => ['required', 'in:price_a_with_tax,price_b_with_tax,price_c_with_tax,price_d_with_tax'],
            'category_id' => ['nullable', 'exists:product_categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'product_id' => ['nullable', 'exists:products,id'],
        ]);

        $query = Product::query()->where('is_active', true);

        if (!empty($data['category_id'])) {
            $query->where('category_id', $data['category_id']);
        }

        if (!empty($data['brand_id'])) {
            $query->where('brand_id', $data['brand_id']);
        }

        if (!empty($data['product_id'])) {
            $query->where('id', $data['product_id']);
        }

        return $query->get()->map(function ($product) use ($data) {
            $old = (float) $product->{$data['price_field']};
            $new = $data['operation'] === 'add'
                ? $old + ($old * $data['amount'] / 100)
                : $old - ($old * $data['amount'] / 100);

            return [
                'id' => $product->id,
                'code' => $product->code,
                'product_name' => $product->name,
                'old_price' => round($old, 2),
                'new_price' => round($new, 2),
                'price_field' => $data['price_field'],
            ];
        });
    }

    public function apply(Request $request)
    {
        $items = $this->simulate($request);

        foreach ($items as $item) {
            Product::where('id', $item['id'])
                ->where('is_active', true)
                ->update([
                $item['price_field'] => $item['new_price'],
            ]);
        }

        return response()->json([
            'message' => 'Prices updated successfully',
            'items' => $items,
        ]);
    }
}
