{{-- Vista: Nuevo registro de Órdenes de Compra. Muestra el formulario para crear un registro de Órdenes de Compra. --}}
@extends('layouts.app')

@section('content')

<h3 class="mb-4">Nueva Orden de Compra</h3>

<div class="card">
    <div class="card-body">
        <div class="row">

            <div class="col-md-4 mb-3">
                <label>Proveedor</label>
                <select class="form-select" id="provider" data-remote-url="{{ url('/api/providers') }}" data-remote-placeholder="Buscar proveedor por nombre, código o identificación..."></select>
            </div>

            <div class="col-md-3 mb-3">
                <label>Fecha emisión</label>
                <input id="issueDate" type="date" class="form-control">
            </div>

            <div class="col-md-3 mb-3">
                <label>Nro. orden</label>
                <input id="orderNumber" class="form-control">
            </div>

            <div class="col-md-2 mb-3">
                <label>Moneda</label>
                <select class="form-select" id="currencyName">
                    <option value="Pesos">Pesos</option>
                    <option value="Dólares">Dólares</option>
                </select>
            </div>

            <div class="col-md-12 mb-3">
                <label>Observaciones</label>
                <textarea id="notes" class="form-control" rows="3"></textarea>
            </div>

        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">Productos solicitados</h5>

        <button type="button" class="btn btn-success btn-sm" onclick="addRow()">
            Agregar producto
        </button>
    </div>

    <div class="card-body">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th style="width:40%">Producto</th>
                    <th>Cant.</th>
                    <th>P.Unit.</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>

            <tbody id="itemsBody"></tbody>
        </table>

        <div class="text-end mt-3">
            <h4>Total orden: <span id="orderTotal">$0,00</span></h4>
        </div>
    </div>
</div>

<div class="text-end mt-4">
    <button type="button" class="btn btn-outline-secondary me-2" onclick="location.href="{{ url('/demo/purchase-orders') }}"">
        Cancelar
    </button>

    <button type="button" class="btn btn-primary" onclick="saveOrder()">
        Guardar Orden
    </button>
</div>

<script>
let products = [];
let providers = [];

function money(value) {
    return Number(value ?? 0).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS'
    });
}

async function loadData() {
    erpEnhanceRemoteSelect(provider);

    orderNumber.value = 'OC-' + Date.now();

    addRow();
}

function addRow() {
    const tr = document.createElement('tr');

    tr.innerHTML = `
        <td>
            <select class="form-select product" data-remote-url="{{ url('/api/products') }}" data-remote-placeholder="Buscar producto (mín. 3 caracteres)"><option value=""></option></select>
        </td>

        <td>
            <input class="form-control qty" type="number" min="0.01" step="0.01" value="1">
        </td>

        <td>
            <input class="form-control price" type="number" min="0" step="0.01" value="0">
        </td>

        <td class="total">${money(0)}</td>

        <td>
            <button type="button" class="btn btn-danger btn-sm remove-row">
                X
            </button>
        </td>
    `;

    itemsBody.appendChild(tr);

    tr.querySelector('.product').addEventListener('change', () => applyProductPrice(tr));
    tr.querySelector('.qty').addEventListener('input', calculateTotals);
    tr.querySelector('.price').addEventListener('input', calculateTotals);

    tr.querySelector('.remove-row').addEventListener('click', () => {
        tr.remove();
        calculateTotals();
    });

    applyProductPrice(tr);
}

function applyProductPrice(tr) {
    const selected = tr.querySelector('.product').selectedOptions[0];
    tr.querySelector('.price').value = selected?.dataset?.price ?? 0;
    calculateTotals();
}

function calculateTotals() {
    let total = 0;

    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        const qty = Number(tr.querySelector('.qty').value || 0);
        const price = Number(tr.querySelector('.price').value || 0);
        const lineTotal = qty * price;

        total += lineTotal;
        tr.querySelector('.total').innerText = money(lineTotal);
    });

    orderTotal.innerText = money(total);
}

async function saveOrder() {
    const items = [];

    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        items.push({
            product_id: Number(tr.querySelector('.product').value),
            quantity: Number(tr.querySelector('.qty').value),
            unit_price: Number(tr.querySelector('.price').value)
        });
    });

    const res = await fetch(`${window.APP_BASE_URL}/api/purchase-orders`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json'
        },
        body: JSON.stringify({
            provider_id: Number(provider.value),
            issue_date: issueDate.value,
            order_number: orderNumber.value,
            currency_name: currencyName.value,
            notes: notes.value,
            items
        })
    });

    if (res.ok) {
        const order = await res.json();
        location = `${window.APP_BASE_URL}/demo/purchase-orders/` + order.id;
    } else {
        const error = await res.json();
        console.log(error);
        alert(error.message ?? 'Error al guardar orden.');
    }
}

issueDate.value = new Date().toISOString().substring(0, 10);

loadData();
</script>

@endsection
