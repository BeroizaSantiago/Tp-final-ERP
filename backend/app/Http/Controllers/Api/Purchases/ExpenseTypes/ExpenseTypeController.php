<?php

namespace App\Http\Controllers\Api\Purchases\ExpenseTypes;

use App\Http\Controllers\Controller;
use App\Models\Purchases\ExpenseType;
use Illuminate\Http\Request;

/**
 * Gestiona el catalogo de tipos de gastos.
 *
 * Permite listar y crear tipos de gasto con descripcion y estado activo.
 * El resto de las operaciones CRUD conserva la estructura pendiente.
 */
class ExpenseTypeController extends Controller
{
    public function index()
    {
        return ExpenseType::orderBy('name')->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $data['is_active'] ?? true;

        return ExpenseType::create($data);
    }

    public function update(Request $request, ExpenseType $expenseType)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $expenseType->update($data);

        return $expenseType->fresh();
    }

    public function destroy(ExpenseType $expenseType)
    {
        $expenseType->delete();

        return response()->json(['message' => 'Tipo de gasto eliminado']);
    }
}
