{{-- Vista: Listado de Cupones de Tarjeta. Muestra la consulta principal y las acciones disponibles de Cupones de Tarjeta. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Cupones de Tarjeta</h4>
        <small class="text-muted">Listado y seguimiento de cupones</small>
    </div>

    <a href="{{ url('/demo/finance/card-coupons/reconciliation') }}" class="btn btn-primary">
        Conciliación
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5>Filtros</h5><br>
        <div class="row align-items-end">
            <div class="col-md-3 mb-3">
                <label>Tarjeta</label>
                <input id="filter_card" class="form-control" placeholder="Buscar tarjeta...">
            </div>

            <div class="col-md-2 mb-3">
                <label>Nro Cupón</label>
                <input id="filter_coupon" class="form-control">
            </div>

            <div class="col-md-2 mb-3">
                <label>Estado</label>
                <select id="filter_status" class="form-select">
                    <option value="">Todos</option>
                    <option>Pendiente</option>
                    <option>Conciliado</option>
                    <option>Cobrado</option>
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label>Fecha Desde</label>
                <input id="filter_from" type="date" class="form-control">
            </div>

            <div class="col-md-2 mb-3">
                <label>Fecha Hasta</label>
                <input id="filter_to" type="date" class="form-control">
            </div>

            <div class="col-md-1 mb-3">
                <button class="btn btn-secondary w-100" onclick="clearFilters()">X</button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Tarjeta</th>
                    <th>Nro. Tarjeta</th>
                    <th>Plan</th>
                    <th>Nro Lote</th>
                    <th>Nro Cupón</th>
                    <th class="text-end">Cuotas</th>
                    <th>Estado</th>
                    <th class="text-end">Monto Venta</th>
                    <th class="text-end">Comisión</th>
                    <th class="text-end">Recargo</th>
                    <th>Fecha Ingreso</th>
                    <th>Fecha Estimada</th>
                    <th>Tipo Tarjeta</th>
                    <th>Nro Comprobante</th>
                    <th>Cliente</th>
                    <th>Nro Comercio</th>
                </tr>
            </thead>

            <tbody id="rows">
                <tr>
                    <td colspan="16" class="text-center py-4 text-muted">Cargando cupones...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let coupons = [];

function money(v) {
    return Number(v ?? 0).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS'
    });
}

function render(items) {
    if (!items.length) {
        rows.innerHTML = `
            <tr>
                <td colspan="16" class="text-center py-4 text-muted">
                    No hay cupones para mostrar
                </td>
            </tr>
        `;
        return;
    }

    rows.innerHTML = items.map(i => `
        <tr>
            <td><strong>${i.credit_card ?? '-'}</strong></td>
            <td>${i.last_digits_card ?? '-'}</td>
            <td>${i.credit_card_plan ?? '-'}</td>
            <td>${i.lot_number ?? '-'}</td>
            <td>${i.coupon_number ?? '-'}</td>
            <td class="text-end">${i.instalments ?? '-'}</td>
            <td>${i.coupon_status ?? '-'}</td>
            <td class="text-end">${money(i.coupon_amount)}</td>
            <td class="text-end">${money(i.commission)}</td>
            <td class="text-end">${money(i.charge_amount)}</td>
            <td class="text-nowrap">${formatDateTime(i.creation_date)}</td>
            <td class="text-nowrap">${formatDateTime(i.expected_date)}</td>
            <td>${i.credit_card_type ?? '-'}</td>
            <td>${i.receipt_number ?? '-'}</td>
            <td>${i.customer_name ?? '-'}</td>
            <td>${i.trade_number ?? '-'}</td>
        </tr>
    `).join('');
}

function applyFilters() {
    const card = filter_card.value.toLowerCase();
    const coupon = filter_coupon.value.toLowerCase();
    const status = filter_status.value;
    const from = filter_from.value;
    const to = filter_to.value;

    render(coupons.filter(i => {
        const date = i.creation_date ?? '';

        return (
            String(i.credit_card ?? '').toLowerCase().includes(card) &&
            String(i.coupon_number ?? '').toLowerCase().includes(coupon) &&
            (!status || i.coupon_status === status) &&
            (!from || date >= from) &&
            (!to || date <= to)
        );
    }));
}

function clearFilters() {
    filter_card.value = '';
    filter_coupon.value = '';
    filter_status.value = '';
    filter_from.value = '';
    filter_to.value = '';
    render(coupons);
}

['filter_card', 'filter_coupon', 'filter_status', 'filter_from', 'filter_to'].forEach(id => {
    document.getElementById(id).addEventListener('input', applyFilters);
    document.getElementById(id).addEventListener('change', applyFilters);
});

fetch(`${window.APP_BASE_URL}/api/card-coupons`)
    .then(r => r.json())
    .then(data => {
        coupons = data.data ?? data;
        render(coupons);
    });
</script>

@endsection
