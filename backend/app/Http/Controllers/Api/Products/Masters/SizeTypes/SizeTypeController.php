<?php

namespace App\Http\Controllers\Api\Products\Masters\SizeTypes;

use App\Http\Controllers\Controller;
use App\Models\Products\SizeType;
use Illuminate\Http\Request;

/**
 * Gestiona los tipos de talle usados para agrupar talles.
 *
 * Permite administrar el catalogo de tipos y consultar los talles asociados
 * a cada uno.
 */
class SizeTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = SizeType::orderBy('name');

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
            'is_active' => ['nullable', 'boolean'],
        ]);

        return SizeType::create($data);
    }

    public function show(SizeType $sizeType)
    {
        return $sizeType->load('sizes');
    }

    public function update(Request $request, SizeType $sizeType)
    {
        $sizeType->update($request->all());

        return $sizeType->fresh();
    }

    public function destroy(SizeType $sizeType)
    {
        $sizeType->delete();

        return response()->json([
            'message' => 'Size type deleted',
        ]);
    }
}
