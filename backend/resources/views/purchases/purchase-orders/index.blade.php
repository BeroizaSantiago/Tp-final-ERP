{{-- Vista: Listado de Órdenes de Compra. Muestra la consulta principal y las acciones disponibles de Órdenes de Compra. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Órdenes de Compra</h4>
        <small class="text-muted">Solicitudes emitidas a proveedores</small>
    </div>

    <a href="{{ url('/demo/purchase-orders/create') }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>
        Nueva orden
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
            placeholder="Buscar orden..."
        >
    </div>

    <div class="table-responsive text-nowrap">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Nro. Comprobante</th>
                    <th>Proveedor</th>
                    <th>Moneda</th>
                    <th class="text-end">Total</th>
                    <th>Estado</th>
                    <th>Creado Por</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody id="rows">
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        Cargando órdenes...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let orders = [];

function money(value) {
    return Number(value ?? 0).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS'
    });
}

function renderOrders(items) {
    if (!items.length) {
        rows.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    No hay órdenes de compra registradas
                </td>
            </tr>
        `;
        return;
    }

    rows.innerHTML = items.map(o => `
        <tr>
            <td class="text-nowrap">${formatDateTime(o.issue_date)}</td>
            <td><strong>${o.order_number ?? '-'}</strong></td>
            <td>${o.provider_name ?? o.provider?.name ?? '-'}</td>
            <td>${o.currency_name ?? 'Pesos'}</td>
            <td class="text-end">${money(o.total_amount)}</td>
            <td><span class="badge bg-label-warning">${o.status_name ?? 'Pendiente'}</span></td>
            <td>${o.created_by ?? '-'}</td>
            <td class="text-end">
                <a href="${window.APP_BASE_URL}/demo/purchase-orders/${o.id}" class="btn btn-sm btn-primary">
                    Ver
                </a>
            </td>
        </tr>
    `).join('');
}

function loadOrders() {
    fetch(`${window.APP_BASE_URL}/api/purchase-orders`)
        .then(r => r.json())
        .then(data => {
            orders = data.data ?? data;
            renderOrders(orders);
        })
        .catch(error => {
            console.error(error);
            rows.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4 text-danger">
                        Error al cargar órdenes
                    </td>
                </tr>
            `;
        });
}

search.addEventListener('input', e => {
    const q = e.target.value.toLowerCase();

    renderOrders(orders.filter(o =>
        String(o.order_number ?? '').toLowerCase().includes(q) ||
        String(o.provider_name ?? '').toLowerCase().includes(q) ||
        String(o.status_name ?? '').toLowerCase().includes(q)
    ));
});

loadOrders();
</script>

@endsection
