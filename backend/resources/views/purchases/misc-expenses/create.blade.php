{{-- Vista: Nuevo registro de Gastos Varios. Muestra el formulario para crear un registro de Gastos Varios. --}}
@extends('layouts.app')

@section('content')

<h3 class="mb-4">Nuevo Gasto Vario</h3>

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
                    <option>Ticket</option>
                    <option>Recibo</option>
                    <option>Nota de Crédito</option>
                    <option>Nota de Débito</option>
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label>Número</label>
                <input class="form-control" id="receiptNumber">
            </div>

            <div class="col-md-3 mb-3">
                <label>Fecha</label>
                <input type="date" class="form-control" id="issueDate">
            </div>

            <div class="col-md-3 mb-3">
                <label>Sucursal</label>
                <select class="form-select" id="branchName" data-stock-branch required></select>
            </div>

            <div class="col-md-3 mb-3">
                <label>Moneda</label>
                <select class="form-select" id="currencyName">
                    <option>Pesos</option>
                    <option>Dólares</option>
                </select>
            </div>

        </div>

    </div>
</div>

<div class="card mt-4">

    <div class="card-header">
        <h5>Detalle del gasto</h5>
    </div>

    <div class="card-body">

        <div class="row">

            <div class="col-md-3 mb-3">
                <label>Tipo de gasto</label>
                <select class="form-select" id="expenseType"></select>
            </div>

            <div class="col-md-5 mb-3">
                <label>Descripción</label>
                <input class="form-control" id="description">
            </div>

            <div class="col-md-2 mb-3">
                <label>Importe Neto</label>
                <input type="number" class="form-control" id="netAmount" value="0">
            </div>

            <div class="col-md-2 mb-3">
                <label>% IVA</label>
                <select class="form-select" id="iva">
                    <option value="21">21%</option>
                    <option value="10.5">10.5%</option>
                    <option value="0">Exento</option>
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label>Descuento</label>
                <input type="number" class="form-control" id="discount" value="0">
            </div>

            <div class="col-md-2 mb-3">
                <label>Recargo</label>
                <input type="number" class="form-control" id="surcharge" value="0">
            </div>

            <div class="col-md-2 mb-3">
                <label>IVA</label>
                <input class="form-control" id="tax" readonly>
            </div>

            <div class="col-md-2 mb-3">
                <label>Total</label>
                <input class="form-control" id="total" readonly>
            </div>

        </div>

    </div>

</div>

<div class="card mt-4">

    <div class="card-body">

        <label>Observaciones</label>

        <textarea
            id="notes"
            rows="4"
            class="form-control"></textarea>

    </div>

</div>

<div class="text-end mt-4">

    <button class="btn btn-secondary"
        onclick="location=`${window.APP_BASE_URL}/demo/misc-expenses`">

        Cancelar

    </button>

    <button
        class="btn btn-primary"
        onclick="continuePayment()">

        Continuar

    </button>

</div>

<script>

let providers=[];
let expenseTypes=[];

async function loadData(){

    erpEnhanceRemoteSelect(provider);

    expenseTypes=(await fetch(`${window.APP_BASE_URL}/api/expense-types`).then(r=>r.json())).data;

    expenseType.innerHTML=expenseTypes.map(t=>`
        <option value="${t.id}">
            ${t.name}
        </option>
    `).join('');

}

function calculate(){

    const net=Number(netAmount.value);

    const discountValue=Number(discount.value);

    const surchargeValue=Number(surcharge.value);

    const ivaValue=Number(iva.value);

    const taxValue=net*(ivaValue/100);

    tax.value=taxValue.toFixed(2);

    total.value=(net-discountValue+surchargeValue+taxValue).toFixed(2);

}

[
netAmount,
discount,
surcharge,
iva
].forEach(x=>x.oninput=calculate);

function continuePayment(){

    sessionStorage.setItem('miscExpenseDraft',JSON.stringify({

        provider_id:provider.value,

        expense_type_id:expenseType.value,

        issue_date:issueDate.value,

        receipt_type_name:receiptType.value,

        receipt_number:receiptNumber.value,

        branch_name:branchName.value,

        net_amount:Number(netAmount.value),

        discount_amount:Number(discount.value),

        surcharge_amount:Number(surcharge.value),

        tax_amount:Number(tax.value),

        notes:notes.value

    }));

    location="{{ url('/demo/misc-expenses/payment') }}";

}

issueDate.value=new Date().toISOString().substring(0,10);

loadData();

calculate();

</script>

@endsection
