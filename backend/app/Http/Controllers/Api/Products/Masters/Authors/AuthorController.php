<?php

namespace App\Http\Controllers\Api\Products\Masters\Authors;

use App\Http\Controllers\Controller;
use App\Models\Products\Author;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function index(Request $request)
    {
        $query = Author::orderBy('name');

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
            'biography' => ['nullable', 'string'],
            'external_code' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Ingresá el nombre del autor.',
        ]);

        return Author::create($data);
    }

    public function show(Author $author)
    {
        return $author;
    }

    public function update(Request $request, Author $author)
    {
        $author->update($request->all());
        return $author->fresh();
    }

    public function destroy(Author $author)
    {
        $author->delete();
        return response()->json(['message' => 'Autor eliminado']);
    }
}