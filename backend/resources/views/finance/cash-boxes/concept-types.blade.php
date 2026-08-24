{{-- Vista: Tipos de Concepto. Muestra la pantalla o componente funcional correspondiente a Tipos de Concepto. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Tipos de Concepto de Caja</h4>
        <small class="text-muted">Conceptos utilizados para ingresos y egresos de caja</small>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-4 mb-3">
                <label>Nombre</label>
                <input id="name" class="form-control" placeholder="Ej: FACTURA DE VENTA">
            </div>

            <div class="col-md-3 mb-3">
                <label>Tipo de Movimiento</label>
                <select id="movement_type_name" class="form-select">
                    <option value="INGRESO">INGRESO</option>
                    <option value="EGRESO">EGRESO</option>
                </select>
            </div>

            <div class="col-md-3 mb-3">
                <label>Cuenta contable</label>
                <input id="gl_account_name" class="form-control" placeholder="Opcional">
            </div>

            <div class="col-md-2 mb-3">
                <button class="btn btn-primary w-100" onclick="saveConcept()">
                    Agregar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Listado</h5>

        <input
            id="search"
            class="form-control form-control-sm"
            style="max-width:280px"
            placeholder="Buscar concepto..."
        >
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo movimiento</th>
                    <th>Cuenta contable</th>
                    <th class="text-center">Activo</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody id="rows">
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        Cargando conceptos...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let concepts = [];

function activeBadge(value) {
    return value
        ? `<span class="badge bg-label-success">Sí</span>`
        : `<span class="badge bg-label-secondary">No</span>`;
}

function render(items) {
    if (!items.length) {
        rows.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                    No hay conceptos cargados
                </td>
            </tr>
        `;
        return;
    }

    rows.innerHTML = items.map(i => `
        <tr>
            <td>
                <input class="form-control form-control-sm" id="name-${i.id}" value="${i.name ?? ''}">
            </td>

            <td>
                <select class="form-select form-select-sm" id="movement-${i.id}">
                    <option value="INGRESO" ${(i.movement_type_name ?? '') === 'INGRESO' ? 'selected' : ''}>INGRESO</option>
                    <option value="EGRESO" ${(i.movement_type_name ?? '') === 'EGRESO' ? 'selected' : ''}>EGRESO</option>
                </select>
            </td>

            <td>
                <input class="form-control form-control-sm" id="account-${i.id}" value="${i.gl_account_name ?? ''}">
            </td>

            <td class="text-center">
                <select class="form-select form-select-sm" id="active-${i.id}">
                    <option value="1" ${i.is_active ? 'selected' : ''}>Sí</option>
                    <option value="0" ${!i.is_active ? 'selected' : ''}>No</option>
                </select>
            </td>

            <td class="text-end">
                <button class="btn btn-sm btn-success" onclick="updateConcept(${i.id})">
                    Guardar
                </button>
            </td>
        </tr>
    `).join('');
}

function loadConcepts() {
    fetch(`${window.APP_BASE_URL}/api/cash-concept-types`)
        .then(r => r.json())
        .then(data => {
            concepts = data.data ?? data;
            render(concepts);
        });
}

async function saveConcept() {
    const movementName = document.getElementById('movement_type_name').value;

    const payload = {
        name: document.getElementById('name').value.trim(),
        movement_type_id: movementName === 'INGRESO' ? 1 : 2,
        movement_type_name: movementName,
        gl_account_name: document.getElementById('gl_account_name').value.trim(),
        is_active: true
    };

    if (!payload.name) {
        alert('Debe ingresar un nombre.');
        return;
    }

    const res = await fetch(`${window.APP_BASE_URL}/api/cash-concept-types`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json'
        },
        body: JSON.stringify(payload)
    });

    if (res.ok) {
        document.getElementById('name').value = '';
        document.getElementById('gl_account_name').value = '';
        loadConcepts();
    } else {
        console.log(await res.json());
        alert('Error al guardar.');
    }
}

async function updateConcept(id) {
    const movementName = document.getElementById(`movement-${id}`).value;

    const payload = {
        
        name: document.getElementById(`name-${id}`).value.trim(),
        movement_type_name: movementName,
        gl_account_name: document.getElementById(`account-${id}`).value.trim(),
        is_active: document.getElementById(`active-${id}`).value === '1',
        movement_type_id: movementName === 'INGRESO' ? 1 : 2,
        movement_type_name: movementName
    };

    const res = await fetch(`${window.APP_BASE_URL}/api/cash-concept-types/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json'
        },
        body: JSON.stringify(payload)
    });

    if (res.ok) {
        loadConcepts();
    } else {
        console.log(await res.json());
        alert('Error al actualizar.');
    }
}

search.addEventListener('input', e => {
    const q = e.target.value.toLowerCase();

    render(concepts.filter(i =>
        String(i.name ?? '').toLowerCase().includes(q) ||
        String(i.movement_type_name ?? '').toLowerCase().includes(q) ||
        String(i.gl_account_name ?? '').toLowerCase().includes(q)
    ));
});

loadConcepts();
</script>

@endsection