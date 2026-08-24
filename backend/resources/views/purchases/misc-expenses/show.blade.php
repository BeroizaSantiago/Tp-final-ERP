{{-- Vista: Detalle de Gastos Varios. Muestra la información completa de un registro de Gastos Varios. --}}
@extends('layouts.app')

@section('content')

<a href="{{ url('/demo/misc-expenses') }}" class="btn btn-secondary mb-3">
    Volver
</a>

<div id="expenseBox">
    Cargando...
</div>

<script>

const expenseId="{{ $miscExpenseId }}";

function money(value){
    return Number(value ?? 0).toLocaleString('es-AR',{
        style:'currency',
        currency:'ARS'
    });
}

function paymentLabel(method){

    return{

        cash:'Efectivo',
        transfer:'Transferencia',
        third_party_check:'Cheque de terceros',
        own_check:'Cheque propio',
        supplier_account:'Cuenta Corriente',
        retention:'Retención'

    }[method] ?? method;

}

fetch(`${window.APP_BASE_URL}/api/misc-expenses/${expenseId}`)
.then(r=>r.json())
.then(expense=>{

    const payments=expense.payments ?? [];

    const paymentRows=payments.length
        ? payments.map(p=>`

            <tr>

                <td>${paymentLabel(p.payment_method)}</td>

                <td>${p.bank_name ?? '-'}</td>

                <td>${p.reference ?? '-'}</td>

                <td class="text-end">${money(p.total_paid)}</td>

            </tr>

        `).join('')
        :`

            <tr>

                <td colspan="4" class="text-center text-muted">

                    Sin pagos registrados

                </td>

            </tr>

        `;

    expenseBox.innerHTML=`

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h3>

                    Gasto Vario

                    <span class="badge bg-success">

                        ${expense.status_name}

                    </span>

                </h3>

            </div>

            <div>

                <button class="btn btn-primary btn-sm">

                    Imprimir

                </button>

                <button class="btn btn-dark btn-sm">

                    Duplicar

                </button>

            </div>

        </div>

        <div class="card mb-4">

            <div class="card-body">

                <div class="row">

                    <div class="col-md-4">

                        <strong>Proveedor</strong><br>

                        ${expense.provider_name ?? '-'}

                    </div>

                    <div class="col-md-2">

                        <strong>Tipo</strong><br>

                        ${expense.receipt_type_name ?? '-'}

                    </div>

                    <div class="col-md-2">

                        <strong>Comprobante</strong><br>

                        ${expense.receipt_number ?? '-'}

                    </div>

                    <div class="col-md-2">

                        <strong>Fecha</strong><br>

                        ${formatDateTime(expense.issue_date)}

                    </div>

                    <div class="col-md-2">

                        <strong>Sucursal</strong><br>

                        ${expense.branch_name ?? ''}

                    </div>

                </div>

            </div>

        </div>

        <div class="row">

            <div class="col-md-7">

                <div class="card">

                    <div class="card-header">

                        <h5 class="mb-0">

                            Detalle del gasto

                        </h5>

                    </div>

                    <div class="card-body">

                        <table class="table table-bordered">

                            <tbody>

                                <tr>

                                    <th width="35%">Tipo de gasto</th>

                                    <td>

                                        ${expense.expense_type?.name ?? '-'}

                                    </td>

                                </tr>

                                <tr>

                                    <th>Importe Neto</th>

                                    <td>${money(expense.net_amount)}</td>

                                </tr>

                                <tr>

                                    <th>Descuento</th>

                                    <td>${money(expense.discount_amount)}</td>

                                </tr>

                                <tr>

                                    <th>Recargo</th>

                                    <td>${money(expense.surcharge_amount)}</td>

                                </tr>

                                <tr>

                                    <th>IVA</th>

                                    <td>${money(expense.tax_amount)}</td>

                                </tr>

                                <tr>

                                    <th>Percepciones</th>

                                    <td>${money(expense.perception_amount)}</td>

                                </tr>

                                <tr>

                                    <th>Total</th>

                                    <td>

                                        <strong>

                                            ${money(expense.total_amount)}

                                        </strong>

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

            <div class="col-md-5">

                <div class="card">

                    <div class="card-header">

                        <h5 class="mb-0">

                            Formas de Pago

                        </h5>

                    </div>

                    <div class="card-body">

                        <table class="table table-bordered">

                            <thead>

                                <tr>

                                    <th>Medio</th>

                                    <th>Banco</th>

                                    <th>Referencia</th>

                                    <th class="text-end">

                                        Total

                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                ${paymentRows}

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

        <div class="card mt-4">

            <div class="card-body">

                <strong>Observaciones</strong>

                <hr>

                ${expense.notes ?? '<span class="text-muted">Sin observaciones</span>'}

            </div>

        </div>

    `;

});

</script>

@endsection
