{{-- Vista: Listado de Clientes. Muestra la consulta principal y las acciones disponibles de Clientes. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Clientes</h4>
        <small class="text-muted">Listado de clientes registrados</small>
    </div>

    <a href="{{ url('/demo/clients/create') }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>
        Nuevo cliente
    </a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Listado</h5>

        <input
            type="text"
            id="search"
            class="form-control form-control-sm"
            style="max-width:280px"
            placeholder="Buscar cliente..."
        >
    </div>

    <div class="table-responsive text-nowrap">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Nombre Fantasía</th>
                    <th>Documento</th>
                    <th>Localidad</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                                    
                    <th>Activo</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody id="rows">
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">
                        Cargando clientes...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @include('components.api-pagination')
</div>

<script>
let clients = [];
let searchTimer;

function renderClients(items) {
    if (!items.length) {
        rows.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-4 text-muted">
                    No hay clientes registrados
                </td>
            </tr>
        `;
        return;
    }

    rows.innerHTML = items.map(c => `
        <tr>
            <td>${c.code ?? c.id ?? '-'}</td>
            <td><strong>${c.name ?? '-'}</strong></td>
            <td>${c.fantasy_name ?? '-'}</td>
            <td>${c.document_type ?? ''} ${c.document_number ?? ''}</td>
            <td>${c.city ?? '-'}</td>
            <td>${c.first_phone ?? '-'}</td>
            <td>${c.email ?? '-'}</td>
            <td>
                ${c.is_active
                    ? `<span class="badge bg-label-success">Activo</span>`
                    : `<span class="badge bg-label-secondary">Inactivo</span>`
                }
            </td>
            <td class="text-end">
                <a href="${window.APP_BASE_URL}/demo/clients/${c.id}/edit" class="btn btn-sm btn-warning">
                    Editar
                </a>
            </td>
        </tr>
    `).join('');
}

function loadClients(page = 1) {
    const params = new URLSearchParams({ page });
    const query = search.value.trim();
    if (query) params.set('search', query);

    fetch(`${window.APP_BASE_URL}/api/clients?${params}`)
        .then(r => r.json())
        .then(data => {
            clients = data.data ?? data;
            renderClients(clients);
            renderApiPagination(data, loadClients);
        });
}

search.addEventListener('input', e => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadClients(1), 300);
});

loadClients();
</script>

@endsection
