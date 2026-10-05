{{-- Vista: Nuevo registro de Compras. Muestra el formulario para crear un registro de Compras. --}}
@extends('layouts.app')

@section('content')

<h3 class="mb-4">Nueva Compra</h3>

<div class="card">
    <div class="card-body">
        <div class="row">

            <div class="col-md-4 mb-3">
                <label>Proveedor</label>
                <select class="form-select" id="provider" data-remote-url="{{ url('/api/providers') }}" data-remote-placeholder="Buscar proveedor por nombre, código o identificación..."></select>
            </div>

            <div class="col-md-3 mb-3">
                <label>Tipo comprobante</label>
                <select class="form-select" id="receiptType">
                    <option>Factura</option>
                    <option>Nota de Crédito</option>
                    <option>Nota de Débito</option>
                    <option>Ticket</option>
                    <option>Recibo</option>
                    <option>Sin Factura</option>
                </select>
            </div>

            <div class="col-md-1 mb-3">
                <label>Letra</label>
                <select class="form-select" id="letter">
                    <option>A</option>
                    <option>B</option>
                    <option>C</option>
                    <option>X</option>
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label>Pto. Vta.</label>
                <input id="firstNumber" class="form-control">
            </div>

            <div class="col-md-2 mb-3">
                <label>Número</label>
                <input id="secondNumber" class="form-control">
            </div>

            <div class="col-md-3 mb-3">
                <label>Fecha</label>
                <input id="issueDate" type="date" class="form-control">
            </div>

            <div class="col-md-3 mb-3">
                <label>Vencimiento</label>
                <input id="dueDate" type="date" class="form-control">
            </div>

            <div class="col-md-3 mb-3">
                <label>Sucursal</label>
                <select class="form-select" id="branchName" required></select>
            </div>

            <div class="col-md-3 mb-3">
                <label>Depósito</label>
                <select class="form-select" id="warehouseName" required></select>
            </div>

            <div class="col-md-3 mb-3">
                <label>Moneda</label>
                <select class="form-select" id="currencyName">
                    <option value="Pesos">Pesos</option>
                    <option value="Dólares">Dólares</option>
                </select>
            </div>

        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">Productos</h5>

        <button type="button" class="btn btn-success btn-sm" onclick="addRow()">
            Agregar producto
        </button>
    </div>

    <div class="card-body">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th style="width:35%">Producto</th>
                    <th>Cant.</th>
                    <th>P.Unit.</th>
                    <th>%IVA</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>

            <tbody id="itemsBody"></tbody>
        </table>

        <div class="text-end mt-3">
            <h4>Total compra: <span id="purchaseTotal">$0,00</span></h4>
        </div>
    </div>
</div>

<div class="card mt-4"><div class="card-header"><h5 class="mb-0">Percepción aplicada por el proveedor <small class="text-muted">(opcional)</small></h5></div><div class="card-body"><div class="row g-3"><div class="col-md-3"><label class="form-label">Tipo</label><select id="perceptionType" class="form-select"><option value="">Sin percepción</option><option>IVA</option><option>Ingresos Brutos</option><option>Otra</option></select></div><div class="col-md-3"><label class="form-label">Régimen</label><input id="perceptionRegime" class="form-control"></div><div class="col-md-3"><label class="form-label">Importe aplicado</label><input id="perceptionAmount" type="number" min="0" step="0.01" value="0" class="form-control"></div><div class="col-md-3"><label class="form-label">Importe calculado</label><input id="perceptionCalculated" type="number" min="0" step="0.01" value="0" class="form-control"></div></div></div></div>

<div class="text-end mt-4">
    <button type="button" class="btn btn-primary" onclick="savePurchase()">
        Continuar a método de pago
    </button>
</div>

<script>
let products = [];
let providers = [];
let stockLocations = [];

function money(value) {
    return Number(value ?? 0).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS'
    });
}

async function loadData() {
    erpEnhanceRemoteSelect(provider);
    await loadStockLocations();
    addRow();
}

async function loadStockLocations() {
    const response = await fetch(`${window.APP_BASE_URL}/api/stock-locations`);
    if (!response.ok) throw new Error('No se pudieron cargar las sucursales y depósitos.');
    stockLocations = await response.json();
    branchName.innerHTML = stockLocations.map(branch =>
        `<option value="${branch.name}">${branch.name}</option>`
    ).join('');
    loadWarehouses();
}

function loadWarehouses() {
    const branch = stockLocations.find(item => item.name === branchName.value);
    warehouseName.innerHTML = (branch?.warehouses ?? []).map(warehouse =>
        `<option value="${warehouse.name}">${warehouse.name}</option>`
    ).join('');
}

branchName.addEventListener('change', loadWarehouses);

function addRow() {
    const tr = document.createElement('tr');

    tr.innerHTML = `
        <td>
            <select class="form-select product" data-remote-url="{{ url('/api/products') }}" data-remote-placeholder="Buscar producto (mín. 3 caracteres)"><option value=""></option></select>
        </td>

        <td>
            <select class="form-select variant"></select>
        </td>

        <td>
            <input class="form-control color" readonly>
        </td>

        <td>
            <input class="form-control qty" type="number" min="0.01" step="0.01" value="1">
        </td>

        <td>
            <input class="form-control price" type="number" min="0" step="0.01" value="0">
        </td>

        <td>
            <input class="form-control iva" type="number" min="0" step="0.01" value="21">
        </td>

        <td class="total">${money(0)}</td>

        <td>
            <button type="button" class="btn btn-danger btn-sm remove-row">
                X
            </button>
        </td>
    `;

    itemsBody.appendChild(tr);

    tr.querySelector('.product').addEventListener('change', () => loadVariants(tr));
    tr.querySelector('.variant').addEventListener('change', () => applyVariant(tr));
    tr.querySelector('.qty').addEventListener('input', calculateTotals);
    tr.querySelector('.price').addEventListener('input', calculateTotals);
    tr.querySelector('.iva').addEventListener('input', calculateTotals);

    tr.querySelector('.remove-row').addEventListener('click', () => {
        tr.remove();
        calculateTotals();
    });

    loadVariants(tr);
}

async function loadVariants(tr) {
    const productId = tr.querySelector('.product').value;
    const product = tr.querySelector('.product')._remoteSelected ?? (productId ? await fetch(`${window.APP_BASE_URL}/api/products/${productId}`).then(response => response.json()) : null);

    const variantSelect = tr.querySelector('.variant');
    variantSelect.innerHTML = '';

    const variants = product?.variants ?? [];

    if (!variants.length) {
        variantSelect.innerHTML = `<option value="">Sin variante</option>`;
        tr.querySelector('.color').value = '-';
        tr.querySelector('.price').value = product?.price_a_with_tax ?? 0;
        calculateTotals();
        return;
    }

    variantSelect.innerHTML = variants.map(v => {
        const price = v.price_a_with_tax || product.price_a_with_tax || 0;

        return `
            <option 
                value="${v.id}"
                data-color="${v.color?.name ?? '-'}"
                data-price="${price}">
                ${v.size?.name ?? '-'}
            </option>
        `;
    }).join('');

    applyVariant(tr);
}

function applyVariant(tr) {
    const selected = tr.querySelector('.variant').selectedOptions[0];

    tr.querySelector('.color').value = selected?.dataset?.color ?? '-';
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

    purchaseTotal.innerText = money(total);
}

async function savePurchase() {
    const items = [];

    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        const productId = tr.querySelector('.product').value;
        const variantId = tr.querySelector('.variant').value;

        if (!variantId) {
            return;
        }

        items.push({
            product_id: Number(productId),
            product_variant_id: Number(variantId),
            quantity: Number(tr.querySelector('.qty').value),
            unit_price: Number(tr.querySelector('.price').value),
            tax_percentage: Number(tr.querySelector('.iva').value),
            discount_percentage: 0
        });
    });

    if (!items.length) {
        alert('Debe agregar al menos un producto con variante.');
        return;
    }

    const res = await fetch(`${window.APP_BASE_URL}/api/purchases`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json'
        },
        body: JSON.stringify({
            provider_id: Number(provider.value),
            issue_date: issueDate.value,
            payment_due_date: dueDate.value,
            receipt_type_name: receiptType.value,
            letter: letter.value,
            first_number: firstNumber.value,
            second_number: secondNumber.value,
            currency_name: currencyName.value,
            branch_name: branchName.value,
            warehouse_name: warehouseName.value,
            items,
            perceptions: perceptionType.value && Number(perceptionAmount.value)>0 ? [{tax_type:perceptionType.value,regime_name:perceptionRegime.value||perceptionType.value,amount:Number(perceptionAmount.value),calculated_amount:Number(perceptionCalculated.value||perceptionAmount.value),is_automatic:false}] : []
        })
    });

    if (res.ok) {
        const purchase = await res.json();
        location = `${window.APP_BASE_URL}/demo/purchases/` + purchase.id + '/payment-method';
    } else {
        const error = await res.json();
        console.log(error);
        alert(error.message ?? 'Error al guardar compra.');
    }
}

issueDate.value = new Date().toISOString().substring(0, 10);
dueDate.value = issueDate.value;

loadData();
</script>

@endsection
