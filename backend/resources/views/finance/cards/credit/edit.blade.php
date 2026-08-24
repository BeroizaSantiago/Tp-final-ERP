{{-- Vista: Edición de Tarjetas de Crédito. Muestra el formulario para modificar un registro de Tarjetas de Crédito. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Editar Tarjeta</h4>
        <small class="text-muted">Configuración de tarjeta</small>
    </div>

    <a href="{{ url('/demo/finance/credit-cards') }}" class="btn btn-secondary">
        Volver
    </a>
</div>

<div id="box">Cargando...</div>

<script>
const creditCardId = "{{ $creditCardId }}";

function render(card) {
    box.innerHTML = `
        <form id="form">
            <div class="card">
                <div class="card-body">
                    <div class="row">

                        <div class="col-md-3 mb-3">
                            <label>Nombre</label>
                            <input id="name" class="form-control" value="${card.name ?? ''}" required>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label>Tipo Tarjeta</label>
                            <select id="credit_card_type" class="form-select">
                                <option ${card.credit_card_type === 'Crédito' ? 'selected' : ''}>Crédito</option>
                                <option ${card.credit_card_type === 'Débito' ? 'selected' : ''}>Débito</option>
                            </select>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label>Autorizaciones</label>
                            <input id="authorizations" class="form-control" value="${card.authorizations ?? ''}">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label>Nro. Comercio</label>
                            <input id="trade_number" class="form-control" value="${card.trade_number ?? ''}">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label>Activa</label>
                            <select id="is_active" class="form-select">
                                <option value="1" ${card.is_active ? 'selected' : ''}>Sí</option>
                                <option value="0" ${!card.is_active ? 'selected' : ''}>No</option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            <div class="text-end mt-4">
                <button class="btn btn-success">
                    Guardar Cambios
                </button>
            </div>
        </form>
    `;

    form.addEventListener('submit', save);
}

async function save(e) {
    e.preventDefault();

    const payload = {
        name: document.getElementById('name').value.trim(),
        credit_card_type: credit_card_type.value,
        authorizations: authorizations.value,
        trade_number: trade_number.value,
        is_active: is_active.value === '1'
    };

    const res = await fetch(`${window.APP_BASE_URL}/api/credit-cards/${creditCardId}`, {
        method: 'PUT',
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
        alert('Error al actualizar tarjeta.');
    }
}

fetch(`${window.APP_BASE_URL}/api/credit-cards/${creditCardId}`)
    .then(r => r.json())
    .then(render);
</script>

@endsection