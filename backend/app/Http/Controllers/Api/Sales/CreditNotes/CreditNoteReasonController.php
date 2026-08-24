<?php

namespace App\Http\Controllers\Api\Sales\CreditNotes;

use App\Http\Controllers\Controller;
use App\Models\Sales\CreditNoteReason;
use Illuminate\Http\Request;

/**
 * Gestiona el catalogo de motivos utilizados al emitir notas de credito.
 *
 * Permite listar los motivos ordenados por nombre y registrar nuevos motivos
 * con su estado de actividad.
 */
class CreditNoteReasonController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:255'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        return CreditNoteReason::query()->when($data['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))->orderBy('name')->paginate($data['per_page'] ?? 20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return CreditNoteReason::create($data);
    }

    public function update(Request $request, CreditNoteReason $creditNoteReason)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'is_active' => ['required', 'boolean']]);
        $creditNoteReason->update($data);
        return $creditNoteReason;
    }
}
