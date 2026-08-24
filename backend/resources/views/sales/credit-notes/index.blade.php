{{-- Vista: Listado de Notas de Crédito. Muestra la consulta principal y las acciones disponibles de Notas de Crédito. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Notas de Crédito</h4>
        <small class="text-muted">Comprobantes emitidos a clientes</small>
    </div>

    <a href="{{ url('/demo/sales/credit-notes/create') }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>
        Nueva nota de crédito
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
            placeholder="Buscar nota de débito..."
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
                        Cargando notas de débito...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let creditNotes = [];

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
                    No hay notas de débito registradas
                </td>
            </tr>
        `;
        return;
    }

    rows.innerHTML = items.map(invoice => `
        <tr>
            <td class="text-nowrap">${formatDateTime(invoice.issue_date)}</td>

            <td>
                <strong>${invoice.full_number ?? `${invoice.letter ?? ''}-${invoice.first_number ?? ''}`}</strong>
                <br>
                <small class="text-muted">${invoice.receipt_type_name ?? 'Nota de Crédito'}</small>
            </td>

            <td>${invoice.customer_name ?? '-'}</td>

            <td>${invoice.currency_name ?? 'Pesos'}</td>

            <td class="text-end">
                <strong>${money(invoice.total_amount)}</strong>
            </td>

            <td>${statusBadge(invoice.status_name)}</td>

            <td class="text-end">
                <a href="${window.APP_BASE_URL}/demo/sales/credit-notes/${invoice.id}" class="btn btn-sm btn-primary">
                    Ver
                </a>
            </td>
        </tr>
    `).join('');
}

function loadInvoices() {
    fetch(`${window.APP_BASE_URL}/api/credit-notes`)
        .then(r => r.json())
        .then(response => {
            creditNotes = response.data ?? response;
            renderInvoices(creditNotes);
        })
        .catch(error => {
            console.error(error);
            rows.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-danger">
                        Error al cargar notas de crédito
                    </td>
                </tr>
            `;
        });
}

search.addEventListener('input', e => {
    const q = e.target.value.toLowerCase();

    renderInvoices(creditNotes.filter(invoice =>
        String(invoice.full_number ?? '').toLowerCase().includes(q) ||
        String(invoice.customer_name ?? '').toLowerCase().includes(q) ||
        String(invoice.receipt_type_name ?? '').toLowerCase().includes(q) ||
        String(invoice.status_name ?? '').toLowerCase().includes(q)
    ));
});

loadInvoices();
</script>

@endsection
