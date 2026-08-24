{{-- Vista: Nuevo registro de Tarjetas de Crédito. Muestra el formulario para crear un registro de Tarjetas de Crédito. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Nueva Tarjeta</h4>
        <small class="text-muted">Alta de tarjeta</small>
    </div>

    <a href="{{ url('/demo/finance/credit-cards') }}" class="btn btn-secondary">
        Volver
    </a>
</div>

<form id="form">

<div class="card">
    <div class="card-body">
        <div class="row">

            <div class="col-md-3 mb-3">
                <label>Nombre</label>
                <input id="name" class="form-control" required>
            </div>

            <div class="col-md-3 mb-3">
                <label>Tipo Tarjeta</label>
                <select id="credit_card_type" class="form-select">
                    <option>Crédito</option>
                    <option>Débito</option>
                </select>
            </div>

            <div class="col-md-3 mb-3">
                <label>Autorizaciones</label>
                <input id="authorizations" class="form-control">
            </div>

            <div class="col-md-3 mb-3">
                <label>Nro. Comercio</label>
                <input id="trade_number" class="form-control">
            </div>

            <div class="col-md-3 mb-3">
                <label>Activa</label>
                <select id="is_active" class="form-select">
                    <option value="1">Sí</option>
                    <option value="0">No</option>
                </select>
            </div>

        </div>
    </div>
</div>

<div class="text-end mt-4">
    <button class="btn btn-success">
        Guardar Tarjeta
    </button>
</div>

</form>

<script>
form.addEventListener('submit', async e => {
    e.preventDefault();

    const payload = {
        name: document.getElementById('name').value.trim(),
        credit_card_type: credit_card_type.value,
        authorizations: authorizations.value,
        trade_number: trade_number.value,
        is_active: is_active.value === '1'
    };

    const res = await fetch(`${window.APP_BASE_URL}/api/credit-cards`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json'
        },
        body: JSON.stringify(payload)
    });

    if (res.ok) {
        location.href = `${window.APP_BASE_URL}/demo/finance/credit-cards`;
    } else {
        console.log(await res.json());
        alert('Error al guardar tarjeta.');
    }
});
</script>

@endsection