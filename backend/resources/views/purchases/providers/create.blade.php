{{-- Vista: Nuevo registro de Proveedores. Muestra el formulario para crear un registro de Proveedores. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Nuevo proveedor</h4>
        <small class="text-muted">
            Complete los datos del proveedor.
        </small>
    </div>

    <a href="{{ url('/demo/providers') }}" class="btn btn-secondary">
        Volver
    </a>
</div>

<div class="card">
    <div class="card-body">

        <form id="form">

            <div class="row">

                <div class="col-md-2 mb-3">
                    <label class="form-label">Código</label>
                    <input class="form-control" name="code">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Razón Social *</label>
                    <input class="form-control" name="name" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Nombre Fantasía</label>
                    <input class="form-control" name="fantasy_name">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo Documento</label>

                    <select class="form-select" name="document_type">
                        <option value="">Seleccione...</option>
                        <option>CUIT</option>
                        <option>DNI</option>
                        <option>CUIL</option>
                        <option>Pasaporte</option>
                    </select>

                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Número Documento</label>
                    <input class="form-control" name="identification_number">
                </div>

                <div class="col-md-3 mb-3"><label class="form-label">Condición frente al IVA</label><input class="form-control" name="vat_classification"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Número de Ingresos Brutos</label><input class="form-control" name="gross_income_number"></div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Domicilio</label>
                    <input class="form-control" name="address">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Localidad</label>
                    <input class="form-control" name="city_name">
                </div>
                <div class="col-md-4 mb-3"><label class="form-label">Provincia</label><input class="form-control" name="province_name"></div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Teléfono</label>
                    <input class="form-control" name="primary_phone">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <input class="form-control" name="email" type="email" placeholder="compras@proveedor.com">
                </div>

                <div class="col-md-4 mb-3 d-flex align-items-end">

                    <div class="form-check form-switch">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="is_active"
                            checked>

                        <label class="form-check-label">
                            Proveedor activo
                        </label>

                    </div>

                </div>

            </div>

            <hr>

            <button class="btn btn-primary">
                Guardar proveedor
            </button>

            <a href="{{ url('/demo/providers') }}" class="btn btn-outline-secondary">
                Cancelar
            </a>

        </form>

    </div>
</div>

<script>

document.getElementById('form').addEventListener('submit', async e => {

    e.preventDefault();

    const form = new FormData(e.target);

    [
        'code',
        'fantasy_name',
        'document_type',
        'identification_number',
        'vat_classification',
        'gross_income_number',
        'address',
        'city_name',
        'province_name',
        'primary_phone',
        'email'
    ].forEach(field => {

        if(form.get(field)===''){
            form.delete(field);
        }

    });

    if(document.querySelector('[name=is_active]').checked){
        form.set('is_active',1);
    }else{
        form.set('is_active',0);
    }

    const res = await fetch(`${window.APP_BASE_URL}/api/providers`,{

        method:'POST',

        headers:{
            Accept:'application/json'
        },

        body:form

    });

    if(res.ok){

        window.location=`${window.APP_BASE_URL}/demo/providers`;

    }else{

        const error=await res.json();

        console.log(error);

        alert(error.message ?? 'Error al guardar.');

    }

});

</script>

@endsection
