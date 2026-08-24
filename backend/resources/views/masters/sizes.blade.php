{{-- Vista: Talles. Muestra la pantalla o componente funcional correspondiente a Talles. --}}
﻿@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Talles</h4>
        <small class="text-muted">Administracion del catalogo de talles</small>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Nuevo talle</h5>
    </div>

    <div class="card-body">
        <form id="sizeForm">
            <div class="row align-items-end">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Nombre *</label>
                    <input class="form-control" id="name" name="name" placeholder="Ej: M" required>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo de talle</label>
                    <select class="form-select" id="size_type_id" name="size_type_id">
                        <option value="">Cargando...</option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Codigo externo</label>
                    <input class="form-control" id="external_code" name="external_code" placeholder="Ej: M">
                </div>

                <div class="col-md-1 mb-3">
                    <label class="form-label">Orden</label>
                    <input class="form-control" id="web_order" name="web_order" type="number" value="1">
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Activo</label>
                    <select class="form-select" id="is_active" name="is_active">
                        <option value="1">Si</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <div class="col-md-1 mb-3">
                    <button id="saveButton" class="btn btn-primary w-100" type="submit" title="Guardar talle">
                        Guardar<i class="ri-add-line"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h5 class="mb-1">Listado</h5>
            <small class="text-muted">Talles registrados en el sistema</small>
        </div>

        <input id="search" class="form-control form-control-sm" style="max-width:280px;" placeholder="Buscar talle...">
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Codigo externo</th>
                    <th class="text-end">Orden</th>
                    <th class="text-center">Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody id="rows">
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Cargando talles...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let sizes = [];
let sizeTypes = [];

function statusBadge(value) {
    return value
        ? `<span class="badge bg-label-success">Activo</span>`
        : `<span class="badge bg-label-secondary">Inactivo</span>`;
}

function render(items) {
    if (!items.length) {
        rows.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No hay talles registrados</td></tr>`;
        return;
    }

    rows.innerHTML = items.map(item => `
        <tr>
            <td><strong>${item.name ?? '-'}</strong></td>
            <td>${item.size_type?.name ?? item.sizeType?.name ?? '-'}</td>
            <td>${item.external_code ?? '-'}</td>
            <td class="text-end">${item.web_order ?? '-'}</td>
            <td class="text-center">${statusBadge(item.is_active)}</td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-warning" onclick="editSize(${item.id})">
                    Editar
                    <i class="ri-edit-line"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

async function loadSizeTypes() {
    const res = await fetch(`${window.APP_BASE_URL}/api/size-types?lookup=1`, { headers: { Accept: 'application/json' } });
    const data = await res.json();
    sizeTypes = data.data ?? data;

    document.getElementById('size_type_id').innerHTML = '<option value="">Seleccione...</option>';
    sizeTypes.forEach(item => {
        document.getElementById('size_type_id').innerHTML += `<option value="${item.id}">${item.name ?? item.id}</option>`;
    });
}

async function loadSizes() {
    const res = await fetch(`${window.APP_BASE_URL}/api/sizes`, { headers: { Accept: 'application/json' } });
    const data = await res.json();
    sizes = data.data ?? data;
    render(sizes);
}

function editSize(id) {
    const size = sizes.find(item => Number(item.id) === Number(id));
    if (!size) return;

    document.getElementById('name').value = size.name ?? '';
    document.getElementById('size_type_id').value = size.size_type_id ?? '';
    document.getElementById('external_code').value = size.external_code ?? '';
    document.getElementById('web_order').value = size.web_order ?? 1;
    document.getElementById('is_active').value = size.is_active ? '1' : '0';
    document.getElementById('sizeForm').dataset.editId = size.id;

    saveButton.classList.remove('btn-primary');
    saveButton.classList.add('btn-warning');
    saveButton.innerHTML = 'Actualizar <i class="ri-save-line"></i>';
    saveButton.title = 'Guardar cambios';

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('sizeForm').reset();
    delete document.getElementById('sizeForm').dataset.editId;
    document.getElementById('web_order').value = 1;
    document.getElementById('is_active').value = '1';
    saveButton.classList.remove('btn-warning');
    saveButton.classList.add('btn-primary');
    saveButton.innerHTML = 'Guardar <i class="ri-add-line"></i>';
    saveButton.title = 'Guardar talle';
}

document.getElementById('sizeForm').addEventListener('submit', async event => {
    event.preventDefault();

    const editId = document.getElementById('sizeForm').dataset.editId;
    const payload = {
        name: document.getElementById('name').value.trim(),
        size_type_id: document.getElementById('size_type_id').value ? Number(document.getElementById('size_type_id').value) : null,
        external_code: document.getElementById('external_code').value.trim() || null,
        web_order: Number(document.getElementById('web_order').value || 1),
        is_active: document.getElementById('is_active').value === '1'
    };

    if (!payload.name) {
        alert('Ingresa el nombre del talle.');
        return;
    }

    saveButton.disabled = true;
    saveButton.innerHTML = `Guardando... <span class="spinner-border spinner-border-sm"></span>`;

    try {
        const res = await fetch(editId ? `${window.APP_BASE_URL}/api/sizes/${editId}` : `${window.APP_BASE_URL}/api/sizes`, {
            method: editId ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (!res.ok) {
            const errors = data.errors ? Object.values(data.errors).flat().join('\n') : '';
            alert([data.message ?? 'Error al guardar el talle.', errors].filter(Boolean).join('\n'));
            return;
        }

        resetForm();
        await loadSizes();
    } catch (error) {
        console.error(error);
        alert('No se pudo conectar con el servidor.');
    } finally {
        saveButton.disabled = false;
        if (document.getElementById('sizeForm').dataset.editId) {
            saveButton.classList.remove('btn-primary');
            saveButton.classList.add('btn-warning');
            saveButton.innerHTML = 'Actualizar <i class="ri-save-line"></i>';
        } else {
            saveButton.classList.remove('btn-warning');
            saveButton.classList.add('btn-primary');
            saveButton.innerHTML = 'Guardar <i class="ri-add-line"></i>';
        }
    }
});

search.addEventListener('input', event => {
    const query = event.target.value.toLowerCase().trim();
    render(sizes.filter(item =>
        String(item.name ?? '').toLowerCase().includes(query) ||
        String(item.external_code ?? '').toLowerCase().includes(query) ||
        String(item.size_type?.name ?? item.sizeType?.name ?? '').toLowerCase().includes(query)
    ));
});

document.addEventListener('DOMContentLoaded', async () => {
    await loadSizeTypes();
    await loadSizes();
});
</script>

@endsection

