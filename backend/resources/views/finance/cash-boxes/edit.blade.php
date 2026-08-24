{{-- Vista: Edición de Cajas. Muestra el formulario para modificar un registro de Cajas. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Editar Caja</h4>
        <small class="text-muted">Configuración de caja / tesorería</small>
    </div>

    <button class="btn btn-success" onclick="guardar()">
        Guardar cambios
    </button>
</div>

<div id="box">Cargando...</div>

<script>
const cashBoxId = "{{ $cashBoxId }}";

let currencies = [];
let pointOfSales = [];

function renderForm(cashBox) {
    box.innerHTML = `
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">

                    <div class="col-md-3 mb-3">
                        <label>Nombre</label>
                        <input id="name" class="form-control" value="${cashBox.name ?? ''}">
                    </div>

                    <div class="col-md-2 mb-3">
                        <label>Código</label>
                        <input id="code" class="form-control" value="${cashBox.code ?? ''}">
                    </div>

                    <div class="col-md-2 mb-3">
                        <label>Tipo</label>
                        <select id="boxType" class="form-select">
                            <option value="CAJA" ${cashBox.box_type_name === 'CAJA' ? 'selected' : ''}>Caja</option>
                            <option value="TESORERIA" ${cashBox.box_type_name === 'TESORERIA' ? 'selected' : ''}>Tesorería</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>Sucursal</label>
                        <select id="branch" class="form-select" data-stock-branch data-selected="${cashBox.branch_name ?? ''}" required></select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label>Activa</label>
                        <select id="active" class="form-select">
                            <option value="1" ${cashBox.is_active ? 'selected' : ''}>Sí</option>
                            <option value="0" ${!cashBox.is_active ? 'selected' : ''}>No</option>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <strong>Monedas</strong>

                <button type="button" class="btn btn-sm btn-primary" onclick="addCurrency()">
                    Agregar
                </button>
            </div>

            <div class="card-body">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Moneda</th>
                            <th>Saldo último cierre</th>
                            <th>Activa</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody id="currencyRows"></tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <strong>Puntos de Venta</strong>

                <button type="button" class="btn btn-sm btn-primary" onclick="addPos()">
                    Agregar
                </button>
            </div>

            <div class="card-body">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Manual</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody id="posRows"></tbody>
                </table>
            </div>
        </div>
    `;

    renderCurrencies();
    renderPos();
}

function renderCurrencies() {
    currencyRows.innerHTML = currencies.map((c, i) => `
        <tr>
            <td>
                <input class="form-control" id="currency-name-${i}" value="${c.currency_name ?? 'Pesos'}">
            </td>

            <td>
                <input class="form-control" id="currency-balance-${i}" type="number" value="${c.last_closing_balance ?? 0}">
            </td>

            <td>
                <select id="currency-active-${i}" class="form-select">
                    <option value="1" ${c.is_active ? 'selected' : ''}>Sí</option>
                    <option value="0" ${!c.is_active ? 'selected' : ''}>No</option>
                </select>
            </td>

            <td class="text-end">
                <button class="btn btn-sm btn-danger" onclick="removeCurrency(${i})">
                    X
                </button>
            </td>
        </tr>
    `).join('');
}

function renderPos() {
    posRows.innerHTML = pointOfSales.map((p, i) => `
        <tr>
            <td>
                <input class="form-control" id="pos-number-${i}" value="${p.number ?? ''}">
            </td>

            <td>
                <select id="pos-manual-${i}" class="form-select">
                    <option value="0" ${!p.is_manual ? 'selected' : ''}>No</option>
                    <option value="1" ${p.is_manual ? 'selected' : ''}>Sí</option>
                </select>
            </td>

            <td class="text-end">
                <button class="btn btn-sm btn-danger" onclick="removePos(${i})">
                    X
                </button>
            </td>
        </tr>
    `).join('');
}

function syncRows() {
    currencies = currencies.map((c, i) => ({
        currency_name: document.getElementById(`currency-name-${i}`).value,
        last_closing_balance: Number(document.getElementById(`currency-balance-${i}`).value || 0),
        is_active: document.getElementById(`currency-active-${i}`).value === '1'
    }));

    pointOfSales = pointOfSales.map((p, i) => ({
        number: document.getElementById(`pos-number-${i}`).value,
        is_manual: document.getElementById(`pos-manual-${i}`).value === '1'
    }));
}

function addCurrency() {
    syncRows();

    currencies.push({
        currency_name: 'Pesos',
        last_closing_balance: 0,
        is_active: true
    });

    renderCurrencies();
}

function removeCurrency(index) {
    syncRows();
    currencies.splice(index, 1);
    renderCurrencies();
}

function addPos() {
    syncRows();

    pointOfSales.push({
        number: '',
        is_manual: false
    });

    renderPos();
}

function removePos(index) {
    syncRows();
    pointOfSales.splice(index, 1);
    renderPos();
}

async function guardar() {
    syncRows();

    const payload = {
        name: document.getElementById('name').value,
        code: document.getElementById('code').value,
        box_type_name: document.getElementById('boxType').value,
        branch_name: document.getElementById('branch').value,
        status_description: 'Cerrada',
        is_active: document.getElementById('active').value === '1',
        currencies,
        point_of_sales: pointOfSales
    };

    const res = await fetch(`${window.APP_BASE_URL}/api/cash-boxes/${cashBoxId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json'
        },
        body: JSON.stringify(payload)
    });

    if (res.ok) {
        location.href = `${window.APP_BASE_URL}/demo/finance/cash-boxes`;
    } else {
        console.log(await res.json());
        alert('Error al actualizar.');
    }
}

fetch(`${window.APP_BASE_URL}/api/cash-boxes/${cashBoxId}`)
    .then(r => r.json())
    .then(cashBox => {
        currencies = cashBox.currencies ?? [];
        pointOfSales = cashBox.point_of_sales ?? cashBox.pointOfSales ?? [];

        if (!currencies.length) {
            currencies.push({
                currency_name: 'Pesos',
                last_closing_balance: 0,
                is_active: true
            });
        }

        renderForm(cashBox);
    });
</script>

@endsection
