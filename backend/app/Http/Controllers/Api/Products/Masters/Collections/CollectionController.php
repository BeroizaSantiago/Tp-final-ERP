<?php

namespace App\Http\Controllers\Api\Products\Masters\Collections;

use App\Http\Controllers\Api\Products\Masters\Concerns\AppliesCatalogDefaults;
use App\Http\Controllers\Controller;
use App\Models\Products\Collection;
use Illuminate\Http\Request;

/**
 * Gestiona el catalogo de colecciones de libros.
 *
 * Cada coleccion puede pertenecer a una editorial, y se puede listar, crear,
 * consultar, actualizar y eliminar.
 */
class CollectionController extends Controller
{
    use AppliesCatalogDefaults;

    public function index(Request $request)
    {
        $query = Collection::with('publisher:id,name')->orderBy('web_order')->orderBy('name');

        if ($request->boolean('lookup')) {
            return $query->where('is_active', true)->get();
        }

        return $query->paginate(min(100, max(10, $request->integer('per_page', 20))));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['nullable'],
            'publisher_id' => ['nullable', 'exists:publishers,id'],
            'name' => ['required', 'string'],
            'external_code' => ['nullable', 'string'],
            'web_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Ingresá el nombre de la colección.',
        ]);

        return Collection::create($this->withCatalogDefaults($data));
    }

    public function show(Collection $collection)
    {
        return $collection->load('publisher');
    }

    public function update(Request $request, Collection $collection)
    {
        $collection->update($this->withCatalogDefaults($request->all()));

        return $collection->fresh();
    }

    public function destroy(Collection $collection)
    {
        $collection->delete();

        return response()->json(['message' => 'Colección eliminada']);
    }
}
