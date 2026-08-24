{{-- Vista: Edición de Clientes. Muestra el formulario para modificar un registro de Clientes. --}}
@extends('layouts.app')

@section('content')

<h3 class="mb-4">Editar Cliente</h3>

<form id="clientForm">

<div class="card mb-4">
    <div class="card-header">
        <strong>Datos del Cliente</strong>
    </div>

    <div class="card-body">

        <div class="row">

            <div class="col-md-4 mb-3">
                <label>Nombre *</label>
                <input id="name" name="name" class="form-control" required>
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
                <label>Descuento</label>
                <input id="discount" class="form-control">
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
                <input id="state" class="form-control">
            </div>

            <div class="col-md-2 mb-3">
                <label>País</label>
                <input id="country" class="form-control">
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
                <input id="email" class="form-control">
            </div>

        </div>

    </div>

</div>

<div class="card">

    <div class="card-header">
        <strong>Observaciones</strong>
    </div>

    <div class="card-body">

        <textarea id="notes" rows="4" class="form-control"></textarea>

    </div>

</div>

<div class="text-end mt-4">

    <button class="btn btn-success">
        Guardar Cambios
    </button>

</div>

</form>

<script>

const clientId="{{ $clientId }}";

let client=null;

fetch(`${window.APP_BASE_URL}/api/clients/${clientId}`)
.then(r=>r.json())
.then(c=>{

    client=c;

    Object.keys(c).forEach(key=>{

        const input=document.getElementById(key);

        if(input){

            input.value=c[key] ?? '';

        }

    });

});

clientForm.onsubmit=async e=>{

    e.preventDefault();

    const payload={

        // `window.name` es una propiedad nativa del navegador; se obtiene el
        // input explícitamente para que el nombre nunca desaparezca del JSON.
        name:document.getElementById('name').value.trim(),
        fantasy_name:fantasy_name.value,

        document_type:document_type.value,
        document_number:document_number.value,

        vat_classification:vat_classification.value,

        payment_condition:payment_condition.value,

        currency:currency.value,

        price_type:price_type.value,

        discount:Number(discount.value),

        address:address.value,
        address_number:address_number.value,
        neighborhood:neighborhood.value,

        city:city.value,
        state:state.value,
        country:country.value,

        zip_code:zip_code.value,

        first_phone:first_phone.value,
        second_phone:second_phone.value,

        email:email.value,

        notes:notes.value,

        is_active:true

    };

    const res=await fetch(`${window.APP_BASE_URL}/api/clients/${clientId}`,{

        method:'PUT',

        headers:{
            'Content-Type':'application/json',
            Accept:'application/json'
        },

        body:JSON.stringify(payload)

    });

    if(res.ok){

        location=`${window.APP_BASE_URL}/demo/clients`;

    }else{

        console.log(await res.json());

        alert('Error al actualizar.');

    }

};

</script>

@endsection
