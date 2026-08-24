{{-- Vista: Nuevo registro de Cajas. Muestra el formulario para crear un registro de Cajas. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Nueva Caja</h4>
        <small class="text-muted">Configuración de caja / tesorería</small>
    </div>

    <button  type="button" class=" btn btn-success" onclick="guardar()">
        Guardar
    </button>
</div>

<div class="card mb-4">
    <div class="card-body">

        <div class="row">

            <div class="col-md-3 mb-3">
                <label>Nombre</label>
                <input id="name" class="form-control">
            </div>

            <div class="col-md-2 mb-3">
                <label>Código</label>
                <input id="code" class="form-control">
            </div>

            <div class="col-md-2 mb-3">
                <label>Tipo</label>

                <select id="boxType" class="form-select">
                    <option value="CAJA">Caja</option>
                    <option value="TESORERIA">Tesorería</option>
                </select>

            </div>

            <div class="col-md-3 mb-3">
                <label>Sucursal</label>
                <select id="branch" class="form-select" data-stock-branch required></select>
            </div>

            <div class="col-md-2 mb-3">

                <label>Activa</label>

                <select id="active" class="form-select">
                    <option value="1">Sí</option>
                    <option value="0">No</option>
                </select>

            </div>

        </div>

    </div>
</div>

<div class="card mb-4">

    <div class="card-header d-flex justify-content-between">

        <strong>Monedas</strong>

        <button  type="button" class="btn btn-sm btn-primary" onclick="addCurrency()">
            Agregar
        </button>

    </div>

    <div class="card-body">

        <table class="table">

            <thead>

                <tr>

                    <th>Moneda</th>

                    <th>Saldo último cierre</th>

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

        <button  type="button" class="btn btn-sm btn-primary" onclick="addPos()">
            Agregar
        </button>

    </div>

    <div class="card-body">

        <table class="table">

            <thead>

                <tr>

                    <th>Número</th>

                    <th></th>

                </tr>

            </thead>

            <tbody id="posRows"></tbody>

        </table>

    </div>

</div>

<script>

const currencies=[];
const pointOfSales=[];

function renderCurrencies() {

    document.getElementById('currencyRows').innerHTML =
        currencies.map((currency, index) => `

            <tr>
                <td>
                    <input
                        type="text"
                        class="form-control"
                        value="${currency.currency_name ?? ''}"
                        oninput="
                            currencies[${index}].currency_name = this.value
                        "
                    >
                </td>

                <td>
                    <input
                        type="number"
                        class="form-control"
                        min="0"
                        step="0.01"
                        value="${currency.last_closing_balance ?? 0}"
                        oninput="
                            currencies[${index}].last_closing_balance =
                                Number(this.value || 0)
                        "
                    >
                </td>

                <td class="text-end">
                    <button
                        type="button"
                        class="btn btn-danger btn-sm"
                        onclick="removeCurrency(${index})"
                    >
                        X
                    </button>
                </td>
            </tr>

        `).join('');
}

function renderPos() {

    document.getElementById('posRows').innerHTML =
        pointOfSales.map((pointOfSale, index) => `

            <tr>
                <td>
                    <input
                        type="text"
                        class="form-control"
                        value="${pointOfSale.number ?? ''}"
                        placeholder="Ej: 0005"
                        oninput="
                            pointOfSales[${index}].number = this.value
                        "
                    >
                </td>

                <td class="text-end">
                    <button
                        type="button"
                        class="btn btn-danger btn-sm"
                        onclick="removePos(${index})"
                    >
                        X
                    </button>
                </td>
            </tr>

        `).join('');
}

function addCurrency(){

    currencies.push({

        currency_name:'Pesos',

        last_closing_balance:0,

        is_active:true

    });

    renderCurrencies();

}

function addPos(){

    pointOfSales.push({

        number:''

    });

    renderPos();

}

function removeCurrency(i){

    currencies.splice(i,1);

    renderCurrencies();

}

function removePos(i){

    pointOfSales.splice(i,1);

    renderPos();

}

async function guardar() {

    const nameValue = document.getElementById('name').value.trim();
    const codeValue = document.getElementById('code').value.trim();
    const boxTypeValue = document.getElementById('boxType').value;
    const branchValue = document.getElementById('branch').value;
    const activeValue = document.getElementById('active').value;

    if (!nameValue) {
        alert('El nombre de la caja es obligatorio.');
        document.getElementById('name').focus();
        return;
    }

    const payload = {
        name: nameValue,
        code: codeValue || null,
        box_type_name: boxTypeValue,
        branch_name: branchValue || null,
        is_active: activeValue === '1',

        currencies: currencies.map(currency => ({
            ...currency,
            last_closing_balance: Number(
                currency.last_closing_balance ?? 0
            )
        })),

        point_of_sales: pointOfSales
            .filter(pos => String(pos.number ?? '').trim() !== '')
            .map(pos => ({
                ...pos,
                number: String(pos.number).trim()
            }))
    };

    console.log('Payload enviado:', payload);

    try {

        const response = await fetch(`${window.APP_BASE_URL}/api/cash-boxes`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (!response.ok) {

            console.error('Error del servidor:', data);

            const validationErrors = data.errors
                ? Object.values(data.errors).flat().join('\n')
                : data.message ?? 'Error al guardar la caja.';

            alert(validationErrors);
            return;
        }

        window.location.href = `${window.APP_BASE_URL}/demo/finance/cash-boxes`;

    } catch (error) {

        console.error(error);

        alert(
            'No se pudo conectar con el servidor: ' +
            error.message
        );
    }
}

addCurrency();

</script>

@endsection
