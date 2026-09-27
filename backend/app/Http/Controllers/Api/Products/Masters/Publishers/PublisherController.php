<?php

namespace App\Http\Controllers\Api\Products\Masters\Publishers;

use App\Http\Controllers\Controller;
use App\Models\Products\Publisher;
use Illuminate\Http\Request;

/**
 * Gestiona el catalogo de editoriales.
 *
 * Expone operaciones para listar, crear, consultar, actualizar y eliminar
 * editoriales, que en el catalogo de libros son las casas editoras.
 */
class PublisherController extends Controller
{
    public function index(Request $request)
    {
        $query = Publisher::orderBy('name');

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
            'country' => ['nullable', 'string'],
            'website' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Ingresá el nombre de la editorial.',
        ]);

        return Publisher::create($data);
    }

    public function show(Publisher $publisher)
    {
        return $publisher;
    }

    public function update(Request $request, Publisher $publisher)
    {
        $publisher->update($request->all());

        return $publisher->fresh();
    }

    public function destroy(Publisher $publisher)
    {
        $publisher->delete();

        return response()->json(['message' => 'Editorial eliminada']);
    }
}
