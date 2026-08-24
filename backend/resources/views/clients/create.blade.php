{{-- Vista: Nuevo registro de Clientes. Muestra el formulario para crear un registro de Clientes. --}}
@extends('layouts.app')

@section('content')

<h3 class="mb-4">Nuevo Cliente</h3>

<form id="clientForm">

<div class="card mb-4">
    <div class="card-header">
        <strong>Datos del Cliente</strong>
    </div>

    <div class="card-body">

        <div class="row">

            <div class="col-md-4 mb-3">
                <label>Nombre *</label>
                <input id="name" class="form-control" required>
            </div>

            <div class="col-md-4 mb-3">
                <label>Nombre Fantasía</label>
                <input id="fantasy_name" class="form-control">
            </div>

            <div class="col-md-2 mb-3">
                <label>Tipo Documento</label>
                <select id="document_type" class="form-select">
                    <option>CUIT</option>
                    <option>DNI</option>
                    <option>CUIL</option>
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label>Nro Documento</label>
                <input id="document_number" class="form-control">
            </div>

            <div class="col-md-3 mb-3">
                <label>Fecha de nacimiento</label>
                <input id="birth_date" type="date" class="form-control" max="{{ now()->toDateString() }}">
            </div>

            <div class="col-md-3 mb-3">
                <label>Categoría IVA</label>
                <select id="vat_classification" class="form-select">
                    <option>Resp. Inscripto</option>
                    <option>Monotributo</option>
                    <option>Consumidor Final</option>
                    <option>Exento</option>
                </select>
            </div>

            <div class="col-md-3 mb-3">
                <label>Condición Pago</label>
                <select id="payment_condition" class="form-select">
                    <option>CONTADO</option>
                    <option>CTA CTE</option>
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label>Moneda</label>
                <select id="currency" class="form-select">
                    <option>Pesos</option>
                    <option>Dólares</option>
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label>Lista Precio</label>
                <select id="price_type" class="form-select">
                    <option>Precio A</option>
                    <option>Precio B</option>
                    <option>Precio C</option>
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label>Descuento %</label>
                <input id="discount" class="form-control" value="0">
            </div>

        </div>

    </div>

</div>

<div class="card mb-4">

    <div class="card-header">
        <strong>Domicilio</strong>
    </div>

    <div class="card-body">

        <div class="row">

            <div class="col-md-4 mb-3">
                <label>Dirección</label>
                <input id="address" class="form-control">
            </div>

            <div class="col-md-2 mb-3">
                <label>Número</label>
                <input id="address_number" class="form-control">
            </div>

            <div class="col-md-2 mb-3">
                <label>Barrio</label>
                <input id="neighborhood" class="form-control">
            </div>

            <div class="col-md-2 mb-3">
                <label>Ciudad</label>
                <input id="city" class="form-control">
            </div>

            <div class="col-md-2 mb-3">
                <label>Provincia</label>
                <input id="state" class="form-control" value="Neuquén">
            </div>

            <div class="col-md-2 mb-3">
                <label>País</label>
                <input id="country" class="form-control" value="Argentina">
            </div>

            <div class="col-md-2 mb-3">
                <label>C.P.</label>
                <input id="zip_code" class="form-control">
            </div>

            <div class="col-md-3 mb-3">
                <label>Teléfono</label>
                <input id="first_phone" class="form-control">
            </div>

            <div class="col-md-3 mb-3">
                <label>Segundo teléfono</label>
                <input id="second_phone" class="form-control">
            </div>

            <div class="col-md-4 mb-3">
                <label>Email</label>
                <input id="email" type="email" class="form-control">
            </div>

        </div>

    </div>

</div>

<div class="card">

    <div class="card-header">
        <strong>Observaciones</strong>
    </div>

    <div class="card-body">

        <textarea id="notes" class="form-control" rows="4"></textarea>

    </div>

</div>

<div class="text-end mt-4">

    <button class="btn btn-success">
        Guardar Cliente
    </button>

</div>

</form>

<script>

clientForm.onsubmit = async e => {

    e.preventDefault();

   const payload = {
    name: document.getElementById('name').value,
    fantasy_name: document.getElementById('fantasy_name').value,
    document_type: document.getElementById('document_type').value,
    document_number: document.getElementById('document_number').value,
    birth_date: document.getElementById('birth_date').value || null,
    vat_classification: document.getElementById('vat_classification').value,
    payment_condition: document.getElementById('payment_condition').value,
    currency: document.getElementById('currency').value,
    price_type: document.getElementById('price_type').value,
    discount: Number(document.getElementById('discount').value),

    address: document.getElementById('address').value,
    address_number: document.getElementById('address_number').value,
    neighborhood: document.getElementById('neighborhood').value,
    city: document.getElementById('city').value,
    state: document.getElementById('state').value,
    country: document.getElementById('country').value,
    zip_code: document.getElementById('zip_code').value,

    first_phone: document.getElementById('first_phone').value,
    second_phone: document.getElementById('second_phone').value,
    email: document.getElementById('email').value,

    notes: document.getElementById('notes').value,

    is_active: true
};

    const res = await fetch(`${window.APP_BASE_URL}/api/clients`,{

        method:'POST',

        headers:{
            'Content-Type':'application/json',
            Accept:'application/json'
        },

        body:JSON.stringify(payload)

    });

    if(res.ok){
        const client = await res.json();
        const returnTo = new URLSearchParams(location.search).get('return_to');

        if (returnTo) {
            sessionStorage.setItem('invoice.new_client_id', client.id);
            location = returnTo;
        } else {
            location=`${window.APP_BASE_URL}/demo/clients`;
        }

    }else{

        console.log(await res.json());

        alert('Error al guardar.');

    }

};

</script>

@endsection
