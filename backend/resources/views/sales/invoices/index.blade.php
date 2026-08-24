{{-- Vista: Listado de Facturas de Venta. Muestra la consulta principal y las acciones disponibles de Facturas de Venta. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Facturas de Venta</h4>
        <small class="text-muted">Comprobantes emitidos a clientes</small>
    </div>

    <a href="{{ url('/demo/sales/create') }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>
        Nueva factura
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
            placeholder="Buscar factura..."
        >
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Comprobante</th>
                    <th>Cliente</th>
                    <th>Moneda</th>
                    <th class="text-end">Total</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody id="rows">
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        Cargando facturas...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @include('components.api-pagination')
</div>

<script>
let invoices = [];
let searchTimer;

function money(value) {
    return Number(value ?? 0).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS'
    });
}

function statusBadge(status) {
    const text = String(status ?? '').toLowerCase();

    if (text.includes('cob') || text.includes('pag')) {
        return `<span class="badge bg-label-success">${status}</span>`;
    }

    if (text.includes('pend')) {
        return `<span class="badge bg-label-warning">${status}</span>`;
    }

    if (text.includes('anu') || text.includes('cancel')) {
        return `<span class="badge bg-label-danger">${status}</span>`;
    }

    return `<span class="badge bg-label-secondary">${status ?? '-'}</span>`;
}

function renderInvoices(items) {
    if (!items.length) {
        rows.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    No hay facturas registradas
                </td>
            </tr>
        `;
        return;
    }

    rows.innerHTML = items.map(invoice => `
        <tr>
            <td class="text-nowrap">${formatDateTime(invoice.issue_date)}</td>

            <td>
                <strong>${invoice.display_number ?? invoice.full_number ?? `${invoice.letter ?? ''}-${invoice.first_number ?? ''}`}</strong>
                <br>
                <small class="text-muted">${invoice.receipt_type_name ?? 'Factura'}</small>
            </td>

            <td>${invoice.customer_name ?? '-'}</td>

            <td>${invoice.currency_name ?? 'Pesos'}</td>

            <td class="text-end">
                <strong>${money(invoice.total_amount)}</strong>
            </td>

            <td>${statusBadge(invoice.status_name)}</td>

            <td class="text-end">
                <a href="${window.APP_BASE_URL}/demo/sales/${invoice.id}" class="btn btn-sm btn-primary">
                    Ver
                </a>
            </td>
        </tr>
    `).join('');
}

function loadInvoices(page = 1) {
    const params = new URLSearchParams({ page });
    const query = search.value.trim();
    if (query) params.set('search', query);

    fetch(`${window.APP_BASE_URL}/api/invoices?${params}`)
        .then(r => r.json())
        .then(response => {
            invoices = response.data ?? response;
            renderInvoices(invoices);
            renderApiPagination(response, loadInvoices);
        })
        .catch(error => {
            console.error(error);
            rows.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-danger">
                        Error al cargar facturas
                    </td>
                </tr>
            `;
        });
}

search.addEventListener('input', e => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadInvoices(1), 300);
});

loadInvoices();
</script>

@endsection
