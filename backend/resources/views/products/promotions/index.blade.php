{{-- Vista: Listado de Promociones de Venta. Muestra la consulta principal y las acciones disponibles. --}}
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Promociones de Venta</h4>
        <small class="text-muted">Beneficios opcionales disponibles durante la venta</small>
    </div>
    <a href="{{ url('/demo/sales-promotions/create') }}" class="btn btn-primary">Nueva promoción</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form id="filters" class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Nombre</label>
                <input id="search" class="form-control" placeholder="Buscar promoción...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select id="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="active">Activas</option>
                    <option value="inactive">Inactivas</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Vigencia</label>
                <select id="validity" class="form-select">
                    <option value="">Todas</option>
                    <option value="current">Vigentes</option>
                    <option value="future">Futuras</option>
                    <option value="expired">Vencidas</option>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-primary w-100">Buscar</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Vigencia</th>
                    <th>Alcance</th>
                    <th>Descuento</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="rows"></tbody>
        </table>
    </div>
    @include('components.api-pagination')
</div>

<script>
let page = 1;
const typeName = value => ({
    percentage: 'Porcentaje',
    fixed_amount: 'Importe fijo',
    special_price: 'Precio especial',
    combo: 'Combo'
})[value] ?? value;
const scopeName = value => ({
    all: 'Todos',
    product: 'Productos',
    category: 'Categorías',
    brand: 'Marcas'
})[value] ?? value;

async function load(target = 1) {
    page = target;
    const params = new URLSearchParams({ page: target });
    if (search.value) params.set('search', search.value);
    if (status.value) params.set('status', status.value);
    if (validity.value) params.set('validity', validity.value);

    const response = await fetch(`${window.APP_BASE_URL}/api/sales-promotions?${params}`);
    const data = await response.json();
    rows.innerHTML = data.data.length
        ? data.data.map(item => `
            <tr>
                <td><strong>${item.name}</strong></td>
                <td>${typeName(item.discount_type)}</td>
                <td>${item.date_from ? formatDateTime(item.date_from) : 'Sin inicio'} — ${item.date_to ? formatDateTime(item.date_to) : 'Sin vencimiento'}</td>
                <td>${scopeName(item.applies_to)}</td>
                <td>${item.discount_type === 'percentage' ? item.discount_value + '%' : '$ ' + Number(item.discount_value).toLocaleString('es-AR')}</td>
                <td><span class="badge ${item.is_active ? 'bg-label-success' : 'bg-label-secondary'}">${item.is_active ? 'Activa' : 'Inactiva'}</span></td>
                <td class="text-end">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-icon btn-outline-secondary" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-label="Acciones de la promoción"><i class="icon-base ri ri-more-2-line"></i></button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="${window.APP_BASE_URL}/demo/sales-promotions/${item.id}"><i class="icon-base ri ri-eye-line me-2"></i>Ver detalle</a>
                            <a class="dropdown-item" href="${window.APP_BASE_URL}/demo/sales-promotions/${item.id}/edit"><i class="icon-base ri ri-edit-line me-2"></i>Editar</a>
                            <div class="dropdown-divider"></div>
                            <button class="dropdown-item ${item.is_active ? 'text-danger' : 'text-success'}" onclick="togglePromotion(${item.id})"><i class="icon-base ri ${item.is_active ? 'ri-forbid-line' : 'ri-checkbox-circle-line'} me-2"></i>${item.is_active ? 'Desactivar' : 'Activar'}</button>
                        </div>
                    </div>
                </td>
            </tr>
        `).join('')
        : '<tr><td colspan="7" class="text-center py-4 text-muted">Sin promociones</td></tr>';

    renderApiPagination(data, load);
}

async function togglePromotion(id) {
    await fetch(`${window.APP_BASE_URL}/api/sales-promotions/${id}/toggle`, {
        method: 'POST',
        headers: { Accept: 'application/json' }
    });
    alert('Estado de la promoción actualizado correctamente.');
    load(page);
}

filters.onsubmit = event => {
    event.preventDefault();
    load();
};
load();
</script>
@endsection
