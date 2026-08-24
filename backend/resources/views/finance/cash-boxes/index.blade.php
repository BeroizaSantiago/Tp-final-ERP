{{-- Vista: Listado de Cajas. Muestra la consulta principal y las acciones disponibles de Cajas. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Configuración de Cajas</h4>
        <small class="text-muted">Gestión de cajas, tesorerías, monedas y puntos de venta</small>
    </div>

    <a href="{{ url('/demo/finance/cash-boxes/create') }}" class="btn btn-primary">
        Nueva caja
    </a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Listado</h5>

        <input id="search" class="form-control form-control-sm" style="max-width:280px" placeholder="Buscar caja...">
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Código</th>
                    <th>Tipo</th>
                    <th>Sucursal</th>
                    <th>Estado</th>
                    <th>Activo</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody id="rows">
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">Cargando cajas...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let cashBoxes = [];

function activeBadge(value) {
    return value
        ? `<span class="badge bg-label-success">Sí</span>`
        : `<span class="badge bg-label-secondary">No</span>`;
}

function render(items) {
    if (!items.length) {
        rows.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-4 text-muted">
                    No hay cajas registradas
                </td>
            </tr>
        `;
        return;
    }

    rows.innerHTML = items.map(i => `
        <tr>
            <td><strong>${i.name ?? '-'}</strong></td>
            <td>${i.code ?? '-'}</td>
            <td>${i.box_type_name ?? i.box_type_id ?? '-'}</td>
            <td>${i.branch_name ?? i.branch_id ?? '-'}</td>
            <td>${i.status_description ?? '-'}</td>
            <td>${activeBadge(i.is_active)}</td>
            <td class="text-end">
                <a href="${window.APP_BASE_URL}/demo/finance/cash-boxes/${i.id}/edit" class="btn btn-sm btn-warning">
                    Editar
                </a>
            </td>
        </tr>
    `).join('');
}

fetch(`${window.APP_BASE_URL}/api/cash-boxes`)
    .then(r => r.json())
    .then(data => {
        cashBoxes = data.data ?? data;
        render(cashBoxes);
    });

search.addEventListener('input', e => {
    const q = e.target.value.toLowerCase();

    render(cashBoxes.filter(i =>
        String(i.name ?? '').toLowerCase().includes(q) ||
        String(i.code ?? '').toLowerCase().includes(q) ||
        String(i.box_type_name ?? '').toLowerCase().includes(q) ||
        String(i.branch_name ?? '').toLowerCase().includes(q)
    ));
});
</script>

@endsection