{{-- Vista: Formas de pago de Gastos Varios. Permite registrar y consultar los medios de pago de Gastos Varios. --}}
@extends('layouts.app')

@section('content')

<a href="{{ url('/demo/misc-expenses/create') }}" class="btn btn-secondary mb-3">Volver</a>

<h3 class="mb-4">Formas de pago - Gasto Vario</h3>

<div id="box">Cargando...</div>

<script>
let draft = JSON.parse(sessionStorage.getItem('miscExpenseDraft') || 'null');
let expense = null;
let selectedMethod = 'cash';
let payments = [];

function money(value) {
    return Number(value ?? 0).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS'
    });
}

function labelMethod(method) {
    return {
        cash: 'Efectivo',
        transfer: 'Transferencia',
        third_party_check: 'Cheque de terceros',
        own_check: 'Cheque propio',
        supplier_account: 'Cta. Cte proveedor',
        retention: 'Retención'
    }[method] ?? method;
}

function totalDraft() {
    return Number(draft.net_amount ?? 0)
        - Number(draft.discount_amount ?? 0)
        + Number(draft.surcharge_amount ?? 0)
        + Number(draft.tax_amount ?? 0);
}

function paidTotal() {
    return payments.reduce((acc, p) => acc + Number(p.total_paid), 0);
}

function render() {
    if (!draft) {
        box.innerHTML = `
            <div class="alert alert-danger">
                No hay un gasto pendiente para pagar.
            </div>
        `;
        return;
    }

    const total = totalDraft();
    const paid = paidTotal();
    const balance = Math.max(0, total - paid);

    box.innerHTML = `
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="text-muted">TOTAL GASTO</small>
                                <h2>${money(total)}</h2>
                            </div>

                            <div class="text-end">
                                <small class="text-muted">TOTAL A PAGAR</small>
                                <h2 class="text-success">${money(balance)}</h2>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex flex-wrap gap-2">
                            ${methodButton('cash', 'Efectivo')}
                            ${methodButton('transfer', 'Transferencia')}
                            ${methodButton('third_party_check', 'Cheques de terceros')}
                            ${methodButton('own_check', 'Cheques propios')}
                            ${methodButton('supplier_account', 'Cta. Cte proveedor')}
                            ${methodButton('retention', 'Retenciones')}
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" id="methodBox"></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>Resumen formas de pago</h5>

                        <table class="table table-sm table-bordered mt-3">
                            <thead>
                                <tr>
                                    <th>Medio</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${payments.map(p => `
                                    <tr>
                                        <td>${labelMethod(p.payment_method)}</td>
                                        <td class="text-end">${money(p.total_paid)}</td>
                                    </tr>
                                `).join('') || `
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">
                                            Sin pagos agregados
                                        </td>
                                    </tr>
                                `}
                            </tbody>
                        </table>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <span>Saldo</span>
                            <strong>${money(balance)}</strong>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span>Total pagado</span>
                            <strong>${money(paid)}</strong>
                        </div>

                        <div class="mt-4 text-end">
                            <button class="btn btn-success" onclick="saveExpense()">
                                Guardar gasto
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    renderMethod(balance);
}

function methodButton(method, label) {
    return `
        <button
            type="button"
            class="btn ${selectedMethod === method ? 'btn-primary' : 'btn-outline-primary'}"
            onclick="selectMethod('${method}')">
            ${label}
        </button>
    `;
}

function selectMethod(method) {
    selectedMethod = method;
    render();
}

function renderMethod(balance) {
    if (selectedMethod === 'cash') {
        methodBox.innerHTML = `
            <h5>Efectivo</h5>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>Importe</label>
                    <input class="form-control" id="amount" type="number" value="${balance}">
                </div>

                <div class="col-md-4 mb-3">
                    <label>Descuento</label>
                    <input class="form-control" id="discount" type="number" value="0">
                </div>

                <div class="col-md-4 mb-3">
                    <label>Recargo</label>
                    <input class="form-control" id="surcharge" type="number" value="0">
                </div>
            </div>

            <button class="btn btn-dark" onclick="addLocalPayment()">Agregar pago</button>
        `;
    }

    if (selectedMethod === 'transfer') {
        methodBox.innerHTML = `
            <h5>Transferencia</h5>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>Banco</label>
                    <input class="form-control" id="bank_name">
                </div>

                <div class="col-md-4 mb-3">
                    <label>Referencia</label>
                    <input class="form-control" id="reference">
                </div>

                <div class="col-md-4 mb-3">
                    <label>Importe</label>
                    <input class="form-control" id="amount" type="number" value="${balance}">
                </div>
            </div>

            <button class="btn btn-dark" onclick="addLocalPayment()">Agregar pago</button>
        `;
    }

    if (selectedMethod === 'third_party_check' || selectedMethod === 'own_check') {
        methodBox.innerHTML = `
            <h5>${labelMethod(selectedMethod)}</h5>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>Número / Referencia</label>
                    <input class="form-control" id="reference">
                </div>

                <div class="col-md-4 mb-3">
                    <label>Banco</label>
                    <input class="form-control" id="bank_name">
                </div>

                <div class="col-md-4 mb-3">
                    <label>Importe</label>
                    <input class="form-control" id="amount" type="number" value="${balance}">
                </div>
            </div>

            <button class="btn btn-dark" onclick="addLocalPayment()">Agregar pago</button>
        `;
    }

    if (selectedMethod === 'supplier_account') {
        methodBox.innerHTML = `
            <h5>Cuenta corriente proveedor</h5>

            <p class="text-muted">
                Registra el gasto como deuda pendiente con el proveedor.
            </p>

            <input class="form-control mb-3" id="amount" type="number" value="${balance}">

            <button class="btn btn-dark" onclick="addLocalPayment()">Agregar a cuenta corriente</button>
        `;
    }

    if (selectedMethod === 'retention') {
        methodBox.innerHTML = `
            <h5>Retención</h5>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Concepto</label>
                    <input class="form-control" id="reference">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Importe</label>
                    <input class="form-control" id="amount" type="number" value="${balance}">
                </div>
            </div>

            <button class="btn btn-dark" onclick="addLocalPayment()">Agregar retención</button>
        `;
    }
}

function addLocalPayment() {
    const amount = Number(document.getElementById('amount')?.value || 0);
    const discount = Number(document.getElementById('discount')?.value || 0);
    const surcharge = Number(document.getElementById('surcharge')?.value || 0);

    if (amount <= 0) {
        alert('El importe debe ser mayor a 0.');
        return;
    }

    payments.push({
        payment_method: selectedMethod,
        amount,
        discount_amount: discount,
        surcharge_amount: surcharge,
        total_paid: amount - discount + surcharge,
        bank_name: document.getElementById('bank_name')?.value ?? null,
        reference: document.getElementById('reference')?.value ?? null
    });

    render();
}

async function saveExpense() {
    const res = await fetch(`${window.APP_BASE_URL}/api/misc-expenses`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json'
        },
        body: JSON.stringify(draft)
    });

    if (!res.ok) {
        const error = await res.json();
        console.log(error);
        alert(error.message ?? 'Error al guardar gasto.');
        return;
    }

    expense = await res.json();

    for (const payment of payments) {
        await fetch(`${window.APP_BASE_URL}/api/misc-expenses/${expense.id}/payments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json'
            },
            body: JSON.stringify(payment)
        });
    }

    sessionStorage.removeItem('miscExpenseDraft');

    location = `${window.APP_BASE_URL}/demo/misc-expenses/${expense.id}`;
}

render();
</script>

@endsection