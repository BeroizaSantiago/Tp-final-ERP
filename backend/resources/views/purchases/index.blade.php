{{-- Vista: Listado de Compras. Muestra la consulta principal y las acciones disponibles de Compras. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Compras</h4>
        <small class="text-muted">Comprobantes de compra a proveedores</small>
    </div>

    <a href="{{ url('/demo/purchases/create') }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>
        Nueva compra
    </a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Listado de compras</h5>

        <input
            type="text"
            id="search"
            class="form-control form-control-sm"
            style="max-width:280px"
            placeholder="Buscar compra..."
        >
    </div>

    <div class="table-responsive text-nowrap">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo de Comprobante</th>
                    <th>Nro. Comprobante</th>
                    <th>Proveedor</th>
                    <th>Moneda</th>
                    <th class="text-end">Total</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody id="rows">
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        Cargando compras...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let purchases = [];

function money(value) {
    return Number(value ?? 0).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS'
    });
}

function renderPurchases(items) {
    if (!items.length) {
        rows.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    No hay compras registradas
                </td>
            </tr>
        `;
        return;
    }

    rows.innerHTML = items.map(p => `
        <tr>
            <td class="text-nowrap">${formatDateTime(p.issue_date)}</td>
            <td>${p.receipt_type_name ?? '-'}</td>
            <td><strong>${p.full_number ?? '-'}</strong></td>
            <td>${p.provider_name ?? p.provider?.name ?? '-'}</td>
            <td>${p.currency_name ?? 'Pesos'}</td>
            <td class="text-end">${money(p.total_amount)}</td>
            <td><span class="badge bg-label-warning">${p.status_name ?? 'Pendiente'}</span></td>
            <td class="text-end">
                <a href="${window.APP_BASE_URL}/demo/purchases/${p.id}" class="btn btn-sm btn-primary">
                    Ver
                </a>
            </td>
        </tr>
    `).join('');
}

function loadPurchases() {
    fetch(`${window.APP_BASE_URL}/api/purchases`)
        .then(r => r.json())
        .then(data => {
            purchases = data.data ?? data;
            renderPurchases(purchases);
        })
        .catch(error => {
            console.error(error);
            rows.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4 text-danger">
                        Error al cargar compras
                    </td>
                </tr>
            `;
        });
}

search.addEventListener('input', e => {
    const q = e.target.value.toLowerCase();

    renderPurchases(purchases.filter(p =>
        String(p.full_number ?? '').toLowerCase().includes(q) ||
        String(p.provider_name ?? '').toLowerCase().includes(q) ||
        String(p.receipt_type_name ?? '').toLowerCase().includes(q) ||
        String(p.status_name ?? '').toLowerCase().includes(q)
    ));
});

loadPurchases();
</script>

@endsection
