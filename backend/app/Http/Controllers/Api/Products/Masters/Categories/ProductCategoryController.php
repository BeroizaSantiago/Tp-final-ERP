<?php

namespace App\Http\Controllers\Api\Products\Masters\Categories;

use App\Http\Controllers\Controller;
use App\Models\Products\ProductCategory;
use Illuminate\Http\Request;

/**
 * Gestiona el catalogo jerarquico de categorias de productos.
 *
 * Permite listar categorias con sus subcategorias, crear nuevas entradas,
 * consultar relaciones padre-hijo, actualizar datos y eliminar categorias.
 */
class ProductCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductCategory::with('children')->orderBy('web_order')->orderBy('name');

        if ($request->boolean('lookup')) {
            return $query->where('is_active', true)->get();
        }

        return $query->paginate(min(100, max(10, $request->integer('per_page', 20))));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['nullable'],
            'name' => ['required', 'string'],
            'external_code' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:product_categories,id'],
            'web_order' => ['nullable', 'integer'],
            'image' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return ProductCategory::create($data);
    }

    public function show(ProductCategory $productCategory)
    {
        return $productCategory->load('children', 'parent');
    }

    public function update(Request $request, ProductCategory $productCategory)
    {
        $productCategory->update($request->all());

        return $productCategory->fresh();
    }

    public function destroy(ProductCategory $productCategory)
    {
        $productCategory->delete();

        return response()->json([
            'message' => 'Category deleted',
        ]);
    }
}
