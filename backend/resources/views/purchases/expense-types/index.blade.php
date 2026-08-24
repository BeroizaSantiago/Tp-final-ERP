{{-- Vista: Listado de Tipos de Gasto. Muestra la consulta principal y las acciones disponibles de Tipos de Gasto. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Tipos de Gasto</h4>
        <small class="text-muted">Catálogo para clasificar gastos varios</small>
    </div>
</div>

<div class="card">
    <div class="card-body">

        <div class="row mb-3">
            <div class="col-md-5">
                <input id="name" class="form-control" placeholder="Nombre del tipo de gasto">
            </div>

            <div class="col-md-5">
                <input id="description" class="form-control" placeholder="Descripción">
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary w-100" onclick="saveType()">
                    Agregar
                </button>
            </div>
        </div>

        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Activo</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody id="rows">
                <tr>
                    <td colspan="4" class="text-center text-muted">Cargando...</td>
                </tr>
            </tbody>
        </table>

    </div>
</div>

<script>
let types = [];

function render() {
    if (!types.length) {
        rows.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-muted">
                    No hay tipos de gasto cargados
                </td>
            </tr>
        `;
        return;
    }

    rows.innerHTML = types.map(t => `
        <tr>
            <td>
                <input class="form-control form-control-sm" value="${t.name ?? ''}" id="name-${t.id}">
            </td>

            <td>
                <input class="form-control form-control-sm" value="${t.description ?? ''}" id="description-${t.id}">
            </td>

            <td>
                <input type="checkbox" id="active-${t.id}" ${t.is_active ? 'checked' : ''}>
            </td>

            <td class="text-end">
                <button class="btn btn-sm btn-success" onclick="updateType(${t.id})">
                    Guardar
                </button>

                <button class="btn btn-sm btn-danger" onclick="deleteType(${t.id})">
                    Eliminar
                </button>
            </td>
        </tr>
    `).join('');
}

function loadTypes() {
    fetch(`${window.APP_BASE_URL}/api/expense-types`)
        .then(r => r.json())
        .then(data => {
            types = data.data ?? data;
            render();
        });
}

async function saveType() {

    const payload = {
        name: document.getElementById('name').value.trim(),
        description: document.getElementById('description').value.trim(),
        is_active: true
    };

    if (!payload.name) {
        alert('Debe ingresar un nombre.');
        return;
    }

    const res = await fetch(`${window.APP_BASE_URL}/api/expense-types`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    if (res.ok) {

        document.getElementById('name').value = '';
        document.getElementById('description').value = '';

        loadTypes();

    } else {

        console.log(await res.json());
        alert('Error al guardar.');

    }
}

async function updateType(id) {
    const payload = {
        name: document.getElementById(`name-${id}`).value,
        description: document.getElementById(`description-${id}`).value,
        is_active: document.getElementById(`active-${id}`).checked
    };

    const res = await fetch(`${window.APP_BASE_URL}/api/expense-types/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json'
        },
        body: JSON.stringify(payload)
    });

    if (res.ok) {
        loadTypes();
    } else {
        const error = await res.json();
        alert(error.message ?? 'Error al actualizar');
    }
}

async function deleteType(id) {
    if (!await window.erpConfirm('¿Eliminar este tipo de gasto?')) return;

    const res = await fetch(`${window.APP_BASE_URL}/api/expense-types/${id}`, {
        method: 'DELETE',
        headers: {
            Accept: 'application/json'
        }
    });

    if (res.ok) {
        loadTypes();
    } else {
        alert('No se pudo eliminar');
    }
}

loadTypes();
</script>

@endsection
