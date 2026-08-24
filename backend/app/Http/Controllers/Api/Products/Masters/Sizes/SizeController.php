<?php

namespace App\Http\Controllers\Api\Products\Masters\Sizes;

use App\Http\Controllers\Controller;
use App\Models\Products\Size;
use Illuminate\Http\Request;

/**
 * Gestiona los talles disponibles para los productos.
 *
 * Relaciona cada talle con su tipo, respeta el orden de visualizacion web
 * y expone las operaciones de alta, consulta, actualizacion y baja.
 */
class SizeController extends Controller
{
    public function index(Request $request)
    {
        $query = Size::with('sizeType')->orderBy('web_order')->orderBy('name');

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('external_code', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('lookup')) {
            return $query->where('is_active', true)->get();
        }

        return $query->paginate(min(100, max(10, $request->integer('per_page', 20))));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['nullable'],
            'name' => ['required'],
            'external_code' => ['nullable'],
            'size_type_id' => ['nullable', 'exists:size_types,id'],
            'web_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return Size::create($data);
    }

    public function show(Size $size)
    {
        return $size->load('sizeType');
    }

    public function update(Request $request, Size $size)
    {
        $size->update($request->all());

        return $size->fresh();
    }

    public function destroy(Size $size)
    {
        $size->delete();

        return response()->json([
            'message' => 'Size deleted',
        ]);
    }
}
