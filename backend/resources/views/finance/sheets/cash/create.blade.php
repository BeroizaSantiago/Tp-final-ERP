{{-- Vista: Nuevo registro de Planillas de Caja. Muestra el formulario para crear un registro de Planillas de Caja. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Apertura de Caja</h4>
        <small class="text-muted">Nueva planilla de caja</small>
    </div>

    <a href="{{ url('/demo/finance/cash-sheets') }}" class="btn btn-secondary">
        Volver
    </a>
</div>

<form id="form">

<div class="card mb-4">
    <div class="card-body">
        <div class="row">

            <div class="col-md-3 mb-3">
                <label>Caja</label>
                <select id="cash_box_id" class="form-select">
                    <option value="">Cargando...</option>
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label>Punto de venta</label>
                <select id="pos_name" class="form-select" required>
                    <option value="{{ str_pad((string) config('arca.pto_vta', 4), 4, '0', STR_PAD_LEFT) }}" selected>
                        {{ str_pad((string) config('arca.pto_vta', 4), 4, '0', STR_PAD_LEFT) }}
                    </option>
                    <option value="0099">0099</option>
                </select>
            </div>

            <div class="col-md-3 mb-3">
                <label>Sucursal</label>
                <select id="branch_name" class="form-select" data-stock-branch data-warehouse-target="warehouse_name" required></select>
            </div>

            <div class="col-md-3 mb-3">
                <label>Depósito</label>
                <select id="warehouse_name" class="form-select" required></select>
            </div>

            <div class="col-md-2 mb-3">
                <label>Cajero/a</label>
                <input id="cashier_name" class="form-control" value="system" required>
            </div>

            <div class="col-md-2 mb-3">
                <label>Fecha apertura</label>
                <input id="opening_date" type="datetime-local" class="form-control" required>
            </div>

            <div class="col-md-3 mb-3">
                <label>Efectivo inicial</label>
                <input id="opening_cash_amount" type="number" min="0" step="0.01" class="form-control" value="0" required>
            </div>

            <div class="col-md-12 mb-3">
                <label>Observaciones apertura</label>
                <textarea id="opening_observation" rows="4" class="form-control"></textarea>
            </div>

        </div>
    </div>
</div>

<div class="text-end">
    <button class="btn btn-success">
        Guardar Apertura
    </button>
</div>

</form>

<script>
async function loadCashBoxes() {
    const res = await fetch(`${window.APP_BASE_URL}/api/cash-boxes`);
    const json = await res.json();
    const items = json.data ?? json;

    cash_box_id.innerHTML = '<option value="">Seleccione caja...</option>';

    items.forEach(c => {
        cash_box_id.innerHTML += `
            <option value="${c.id}">
                ${c.name ?? c.cash_box_name ?? 'Caja'} ${c.box_type_name ? '- ' + c.box_type_name : ''}
            </option>
        `;
    });
}

function setNow() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    opening_date.value = now.toISOString().slice(0, 16);
}

form.addEventListener('submit', async e => {
    e.preventDefault();

    const payload = {
        cash_box_id: cash_box_id.value ? Number(cash_box_id.value) : null,
        pos_name: pos_name.value,
        cashier_name: cashier_name.value,
        opening_date: opening_date.value,
        opening_cash_amount: Number(opening_cash_amount.value || 0),
        status_name: 'Abierta',
        opening_observation: opening_observation.value,
        branch_name: branch_name.value,
        warehouse_name: warehouse_name.value
    };

    const res = await fetch(`${window.APP_BASE_URL}/api/cash-sheets`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json'
        },
        body: JSON.stringify(payload)
    });

    if (res.ok) {
        const sheet = await res.json();
        location.href = `${window.APP_BASE_URL}/demo/finance/cash-sheets/${sheet.id}`;
    } else {
        const error = await res.json();
        console.log(error);
        alert(error.message ?? 'Error al abrir caja.');
    }
});

setNow();
loadCashBoxes();
</script>

@endsection
