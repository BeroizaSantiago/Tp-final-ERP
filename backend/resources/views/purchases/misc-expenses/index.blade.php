{{-- Vista: Listado de Gastos Varios. Muestra la consulta principal y las acciones disponibles de Gastos Varios. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Gastos Varios</h4>
        <small class="text-muted">Gastos registrados por comprobante</small>
    </div>

    <a href="{{ url('/demo/misc-expenses/create') }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>
        Nuevo gasto
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
            placeholder="Buscar gasto..."
        >
    </div>

    <div class="table-responsive text-nowrap">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo Comprobante</th>
                    <th>Nro. Comprobante</th>
                    <th>Proveedor</th>
                    <th>Tipo Gasto</th>
                    <th class="text-end">Total</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody id="rows">
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        Cargando gastos...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let expenses = [];

function money(value) {
    return Number(value ?? 0).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS'
    });
}

function renderExpenses(items) {
    if (!items.length) {
        rows.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    No hay gastos registrados
                </td>
            </tr>
        `;
        return;
    }

    rows.innerHTML = items.map(e => `
        <tr>
            <td class="text-nowrap">${formatDateTime(e.issue_date)}</td>
            <td>${e.receipt_type_name ?? '-'}</td>
            <td><strong>${e.receipt_number ?? '-'}</strong></td>
            <td>${e.provider_name ?? e.provider?.name ?? '-'}</td>
            <td>${e.expense_type?.name ?? '-'}</td>
            <td class="text-end">${money(e.total_amount)}</td>
            <td><span class="badge bg-label-success">${e.status_name ?? 'Registrado'}</span></td>
            <td class="text-end">
                <a href="${window.APP_BASE_URL}/demo/misc-expenses/${e.id}" class="btn btn-sm btn-primary">
                    Ver
                </a>
            </td>
        </tr>
    `).join('');
}

function loadExpenses() {
    fetch(`${window.APP_BASE_URL}/api/misc-expenses`)
        .then(r => r.json())
        .then(data => {
            expenses = data.data ?? data;
            renderExpenses(expenses);
        })
        .catch(error => {
            console.error(error);
            rows.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4 text-danger">
                        Error al cargar gastos
                    </td>
                </tr>
            `;
        });
}

search.addEventListener('input', e => {
    const q = e.target.value.toLowerCase();

    renderExpenses(expenses.filter(e =>
        String(e.receipt_number ?? '').toLowerCase().includes(q) ||
        String(e.provider_name ?? '').toLowerCase().includes(q) ||
        String(e.receipt_type_name ?? '').toLowerCase().includes(q) ||
        String(e.status_name ?? '').toLowerCase().includes(q)
    ));
});

loadExpenses();
</script>

@endsection
