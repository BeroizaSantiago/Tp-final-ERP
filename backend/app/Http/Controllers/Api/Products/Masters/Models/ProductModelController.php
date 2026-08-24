<?php

namespace App\Http\Controllers\Api\Products\Masters\Models;

use App\Http\Controllers\Controller;
use App\Models\Products\ProductModel;
use Illuminate\Http\Request;

/**
 * Gestiona los modelos comerciales de productos.
 *
 * Permite asociar modelos a marcas, mantener codigos externos y consultar
 * cada modelo junto con su marca relacionada.
 */
class ProductModelController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductModel::with('brand')->orderBy('name');

        if ($request->boolean('lookup')) {
            return $query->where('is_active', true)->get();
        }

        return $query->paginate(min(100, max(10, $request->integer('per_page', 20))));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['nullable'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'name' => ['required', 'string'],
            'external_code' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return ProductModel::create($data);
    }

    public function show(ProductModel $productModel)
    {
        return $productModel->load('brand');
    }

    public function update(Request $request, ProductModel $productModel)
    {
        $productModel->update($request->all());

        return $productModel->fresh();
    }

    public function destroy(ProductModel $productModel)
    {
        $productModel->delete();

        return response()->json([
            'message' => 'Model deleted',
        ]);
    }
}
