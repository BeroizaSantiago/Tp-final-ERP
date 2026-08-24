{{-- Vista: Listado de Tarjetas de Crédito. Muestra la consulta principal y las acciones disponibles de Tarjetas de Crédito. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Tarjetas</h4>
        <small class="text-muted">Configuración de tarjetas de crédito y débito</small>
    </div>

    <a href="{{ url('/demo/finance/credit-cards/create') }}" class="btn btn-primary">
        Nueva Tarjeta
    </a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Listado</h5>
        <input id="search" class="form-control form-control-sm" style="max-width:280px" placeholder="Buscar tarjeta...">
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo de Tarjeta</th>
                    <th>Nro. Comercio</th>
                    <th>Autorizaciones</th>
                    <th class="text-center">Activa</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody id="rows">
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Cargando tarjetas...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let cards = [];

function badge(value) {
    return value
        ? `<span class="badge bg-label-success">Sí</span>`
        : `<span class="badge bg-label-secondary">No</span>`;
}

function render(items) {
    if (!items.length) {
        rows.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                    No hay tarjetas cargadas
                </td>
            </tr>
        `;
        return;
    }

    rows.innerHTML = items.map(i => `
        <tr>
            <td><strong>${i.name ?? '-'}</strong></td>
            <td>${i.credit_card_type ?? '-'}</td>
            <td>${i.trade_number ?? '-'}</td>
            <td>${i.authorizations ?? '-'}</td>
            <td class="text-center">${badge(i.is_active)}</td>
            <td class="text-end">
                <a href="${window.APP_BASE_URL}/demo/finance/credit-cards/${i.id}/edit" class="btn btn-sm btn-warning">
                    Editar
                </a>
            </td>
        </tr>
    `).join('');
}

function loadCards() {
    fetch(`${window.APP_BASE_URL}/api/credit-cards`)
        .then(r => r.json())
        .then(data => {
            cards = data.data ?? data;
            render(cards);
        });
}

search.addEventListener('input', e => {
    const q = e.target.value.toLowerCase();

    render(cards.filter(i =>
        String(i.name ?? '').toLowerCase().includes(q) ||
        String(i.credit_card_type ?? '').toLowerCase().includes(q) ||
        String(i.trade_number ?? '').toLowerCase().includes(q)
    ));
});

loadCards();
</script>

@endsection