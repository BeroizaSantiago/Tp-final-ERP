<?php

namespace App\Http\Controllers\Api\Stock\Movements;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockAdjustmentReason;
use Illuminate\Http\Request;

/**
 * Gestiona el catalogo de motivos disponibles para los ajustes de stock.
 *
 * Permite listar los motivos ordenados por nombre y registrar nuevos motivos
 * con su estado de actividad.
 */
class StockMovementController extends Controller
{
    public function index()
    {
        return StockAdjustmentReason::orderBy('name')->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return StockAdjustmentReason::create($data);
    }
}
