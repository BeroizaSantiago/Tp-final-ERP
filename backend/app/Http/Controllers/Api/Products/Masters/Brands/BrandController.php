<?php

namespace App\Http\Controllers\Api\Products\Masters\Brands;

use App\Http\Controllers\Controller;
use App\Models\Products\Brand;
use Illuminate\Http\Request;

/**
 * Gestiona el catalogo de marcas de productos.
 *
 * Expone operaciones para listar, crear, consultar, actualizar y eliminar
 * marcas con su codigo externo y estado de actividad.
 */
class BrandController extends Controller
{
    public function index(Request $request)
    {
        $query = Brand::orderBy('name');

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
            'is_active' => ['nullable', 'boolean'],
        ]);

        return Brand::create($data);
    }

    public function show(Brand $brand)
    {
        return $brand;
    }

    public function update(Request $request, Brand $brand)
    {
        $brand->update($request->all());
        return $brand->fresh();
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();
        return response()->json(['message' => 'Brand deleted']);
    }
}
