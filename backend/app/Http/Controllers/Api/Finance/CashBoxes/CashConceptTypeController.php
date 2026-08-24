<?php

namespace App\Http\Controllers\Api\Finance\CashBoxes;

use App\Models\Finance\CashConceptType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

/**
 * Controlador de Caja Concepto Tipo.
 *
 * Coordina las solicitudes, validaciones y respuestas del módulo Caja Concepto Tipo del ERP.
 */
class CashConceptTypeController extends Controller
{
    public function index()
    {
        return CashConceptType::orderBy('name')
            ->paginate(50);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'external_id' => ['nullable'],
            'name' => ['required', 'string'],
            'gl_account_id' => ['nullable'],
            'gl_account_name' => ['nullable', 'string'],
            'movement_type_id' => ['required', 'integer'],
            'movement_type_name' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return CashConceptType::create($data);
    }

    public function show(CashConceptType $cashConceptType)
    {
        return $cashConceptType;
    }

    public function update(Request $request, CashConceptType $cashConceptType)
    {
        $data = $request->validate([
            'external_id' => ['nullable'],
            'name' => ['required', 'string'],
            'gl_account_id' => ['nullable'],
            'gl_account_name' => ['nullable', 'string'],
            'movement_type_id' => ['required', 'integer'],
            'movement_type_name' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $cashConceptType->update($data);

        return $cashConceptType->fresh();
    }

    public function destroy(CashConceptType $cashConceptType)
    {
        $cashConceptType->delete();

        return response()->json([
            'message' => 'Concepto eliminado correctamente',
        ]);
    }
}
