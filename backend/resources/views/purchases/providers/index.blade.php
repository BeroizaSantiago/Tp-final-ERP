{{-- Vista: Listado de Proveedores. Muestra la consulta principal y las acciones disponibles de Proveedores. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Proveedores</h4>
        <small class="text-muted">Listado de proveedores registrados</small>
    </div>

    <a href="{{ url('/demo/providers/create') }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>
        Nuevo proveedor
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
            placeholder="Buscar proveedor..."
        >
    </div>

    <div class="table-responsive text-nowrap">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Razón Social</th>
                    <th>Nombre Fantasía</th>
                    <th>Nro Documento</th>
                    <th>Domicilio</th>
                    <th>Localidad</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th class="text-center">Activo</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody id="rows">
                <tr>
                    <td colspan="10" class="text-center py-4 text-muted">
                        Cargando proveedores...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @include('components.api-pagination')
</div>

<script>
let providers = [];
let searchTimer;

function activeBadge(value) {
    return value
        ? `<span class="badge bg-label-success">Activo</span>`
        : `<span class="badge bg-label-secondary">Inactivo</span>`;
}

function renderProviders(items) {
    if (!items.length) {
        document.getElementById('rows').innerHTML = `
            <tr>
                <td colspan="10" class="text-center py-4 text-muted">
                    No hay proveedores para mostrar
                </td>
            </tr>
        `;
        return;
    }

    document.getElementById('rows').innerHTML = items.map(p => `
        <tr>
            <td>${p.code ?? p.id ?? '-'}</td>
            <td>
                <strong>${p.name ?? '-'}</strong>
                <br>
                <small class="text-muted">${p.document_type ?? ''}</small>
            </td>
            <td>${p.fantasy_name ?? '-'}</td>
            <td>${p.identification_number ?? '-'}</td>
            <td>${p.address ?? '-'}</td>
            <td>${p.city_name ?? '-'}</td>
            <td>${p.primary_phone ?? '-'}</td>
            <td>${p.email ? `<a href="mailto:${p.email}">${p.email}</a>` : '-'}</td>
            <td class="text-center">${activeBadge(p.is_active)}</td>
            <td class="text-end">
                <a href="${window.APP_BASE_URL}/demo/providers/${p.id}/edit" class="btn btn-sm btn-warning">
                    Editar
                </a>
            </td>
        </tr>
    `).join('');
}

function loadProviders(page = 1) {
    const params = new URLSearchParams({ page });
    const query = document.getElementById('search').value.trim();
    if (query) params.set('search', query);

    fetch(`${window.APP_BASE_URL}/api/providers?${params}`)
        .then(r => r.json())
        .then(data => {
            providers = data.data ?? data;
            renderProviders(providers);
            renderApiPagination(data, loadProviders);
        })
        .catch(error => {
            console.error(error);
            document.getElementById('rows').innerHTML = `
                <tr>
                    <td colspan="10" class="text-center py-4 text-danger">
                        Error al cargar proveedores
                    </td>
                </tr>
            `;
        });
}

document.getElementById('search').addEventListener('input', e => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadProviders(1), 300);
});

loadProviders();
</script>

@endsection
