<?php

namespace App\Http\Controllers\Api\Products\Masters\Colors;

use App\Http\Controllers\Api\Products\Masters\Concerns\AppliesCatalogDefaults;
use App\Http\Controllers\Controller;
use App\Models\Products\Color;
use Illuminate\Http\Request;

/**
 * Gestiona el catalogo de colores de productos.
 *
 * Permite ordenar colores para la web, guardar su codigo hexadecimal y
 * mantener su estado de actividad.
 */
class ColorController extends Controller
{
    use AppliesCatalogDefaults;

    public function index(Request $request)
    {
        $query = Color::orderBy('web_order')->orderBy('name');

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
            'hex_code' => ['nullable', 'string'],
            'web_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return Color::create($this->withCatalogDefaults($data));
    }

    public function show(Color $color)
    {
        return $color;
    }

    public function update(Request $request, Color $color)
    {
        $color->update($this->withCatalogDefaults($request->all()));
        return $color->fresh();
    }

    public function destroy(Color $color)
    {
        $color->delete();

        return response()->json(['message' => 'Color deleted']);
    }
}
