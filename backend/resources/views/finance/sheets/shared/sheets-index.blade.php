{{-- Vista: Planillas de Caja. Muestra la pantalla o componente funcional correspondiente a Planillas de Caja. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">{{ $title }}</h4>
        <small class="text-muted">{{ $subtitle }}</small>
    </div>

    @if(!empty($createUrl))
        <a href="{{ $createUrl }}" class="btn btn-primary">
            Nueva planilla
        </a>
    @endif
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Listado</h5>

        <input
            id="search"
            class="form-control form-control-sm"
            style="max-width:280px"
            placeholder="Buscar planilla..."
        >
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Caja</th>
                    <th>Número</th>
                    <th>Cajero</th>
                    <th>Punto Vta.</th>
                    <th>Apertura</th>
                    <th>Cierre</th>
                    <th>Estado</th>
                    <th>Sucursal</th>
                    <th>Depósito</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody id="rows">
                <tr>
                    <td colspan="10" class="text-center py-4 text-muted">
                        Cargando...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @include('components.api-pagination')
</div>

<script>
let sheets = [];
let currentPage = 1;
let searchTimer = null;

const apiUrl = @json($apiUrl);
const showBaseUrl = @json($showBaseUrl);

function statusBadge(status) {
    const s = String(status ?? '').toLowerCase();

    if (s.includes('abierta')) {
        return `<span class="badge bg-label-success">${status}</span>`;
    }

    if (s.includes('cerrada')) {
        return `<span class="badge bg-label-secondary">${status}</span>`;
    }

    return `<span class="badge bg-label-warning">${status ?? '-'}</span>`;
}

function render(items) {
    if (!items.length) {
        rows.innerHTML = `
            <tr>
                <td colspan="10" class="text-center py-4 text-muted">
                    No hay registros para mostrar
                </td>
            </tr>
        `;
        return;
    }

    rows.innerHTML = items.map(i => `
        <tr>
            <td><strong>${i.cash_box_name ?? i.cash_box?.name ?? '-'}</strong></td>
            <td>${i.number ?? '-'}</td>
            <td>${i.cashier_name ?? '-'}</td>
            <td>${i.pos_name ?? '-'}</td>
            <td class="text-nowrap">${formatDateTime(i.opening_date)}</td>
            <td class="text-nowrap">${i.closing_date ? formatDateTime(i.closing_date) : '-'}</td>
            <td>${statusBadge(i.status_name)}</td>
            <td>${i.branch_name ?? '-'}</td>
            <td>${i.warehouse_name ?? '-'}</td>
            <td class="text-end">
                <a href="${showBaseUrl}/${i.id}" class="btn btn-sm btn-primary">
                    Ver
                </a>
            </td>
        </tr>
    `).join('');
}

async function loadSheets(page = 1) {
    currentPage = page;
    rows.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-muted">Cargando...</td></tr>';
    const params = new URLSearchParams({ page: String(page), per_page: '20' });
    if (search.value.trim()) params.set('search', search.value.trim());

    const response = await fetch(`${apiUrl}?${params}`);
    const data = await response.json();
    if (!response.ok) throw new Error(data.message ?? 'No se pudieron cargar las planillas.');

        sheets = data.data ?? data;
        render(sheets);
        renderApiPagination(data, loadSheets);
}

search.addEventListener('input', e => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadSheets(1).catch(error => alert(error.message)), 350);
});

loadSheets().catch(error => alert(error.message));
</script>

@endsection
