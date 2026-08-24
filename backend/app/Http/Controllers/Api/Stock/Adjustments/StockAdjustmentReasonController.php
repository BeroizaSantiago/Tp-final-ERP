<?php

namespace App\Http\Controllers\Api\Stock\Adjustments;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockAdjustmentReason;
use Illuminate\Http\Request;

/**
 * Define la estructura del controlador para administrar motivos de ajustes
 * de stock. Sus operaciones CRUD se encuentran pendientes de implementacion.
 */
class StockAdjustmentReasonController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:255'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        return StockAdjustmentReason::query()->when($data['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))->orderBy('name')->paginate($data['per_page'] ?? 20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return StockAdjustmentReason::create($data);
    }

    public function show(StockAdjustmentReason $stockAdjustmentReason)
    {
        return $stockAdjustmentReason;
    }

    public function update(Request $request, StockAdjustmentReason $stockAdjustmentReason)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'is_active' => ['required', 'boolean']]);
        $stockAdjustmentReason->update($data);

        return $stockAdjustmentReason;
    }

    public function destroy(StockAdjustmentReason $stockAdjustmentReason)
    {
        $stockAdjustmentReason->delete();

        return response()->json([
            'message' => 'Deleted',
        ]);
    }
}
