{{-- Vista: Edición de Proveedores. Muestra el formulario para modificar un registro de Proveedores. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Editar proveedor</h4>
        <small class="text-muted">
            Modifique los datos del proveedor.
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
                            id="is_active"
                            name="is_active">

                        <label class="form-check-label">
                            Proveedor activo
                        </label>

                    </div>

                </div>

            </div>

            <hr>

            <button class="btn btn-primary">
                Guardar cambios
            </button>

            <a href="{{ url('/demo/providers') }}" class="btn btn-outline-secondary">
                Cancelar
            </a>

        </form>

    </div>
</div>

<script>

const providerId = "{{ $providerId }}";

function setValue(name,value){

    const input=document.querySelector(`[name="${name}"]`);

    if(input){
        input.value=value ?? '';
    }

}

async function loadProvider(){

    const res=await fetch(`${window.APP_BASE_URL}/api/providers/${providerId}`);

    const provider=await res.json();

    setValue('code',provider.code);
    setValue('name',provider.name);
    setValue('fantasy_name',provider.fantasy_name);
    setValue('document_type',provider.document_type);
    setValue('identification_number',provider.identification_number);
    setValue('vat_classification',provider.vat_classification);
    setValue('gross_income_number',provider.gross_income_number);
    setValue('address',provider.address);
    setValue('city_name',provider.city_name);
    setValue('province_name',provider.province_name);
    setValue('primary_phone',provider.primary_phone);
    setValue('email',provider.email);

    document.getElementById('is_active').checked=provider.is_active;

}

loadProvider();

document.getElementById('form').addEventListener('submit',async e=>{

    e.preventDefault();

    const form=new FormData(e.target);

    form.append('_method','PUT');

    if(document.getElementById('is_active').checked){
        form.set('is_active',1);
    }else{
        form.set('is_active',0);
    }

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
        'primary_phone'
    ].forEach(field=>{

        if(form.get(field)===''){
            form.delete(field);
        }

    });

    const res=await fetch(`${window.APP_BASE_URL}/api/providers/${providerId}`,{

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

        alert(error.message ?? 'Error al actualizar.');

    }

});

</script>

@endsection
