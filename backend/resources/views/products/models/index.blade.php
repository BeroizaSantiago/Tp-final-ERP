{{-- Vista: Listado de Modelos de Producto. Muestra la consulta principal y las acciones disponibles de Modelos de Producto. --}}
﻿@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Modelos de producto</h4>
        <small class="text-muted">Administracion de modelos asociados a marcas</small>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Nuevo modelo</h5>
    </div>

    <div class="card-body">
        <form id="modelForm">
            <div class="row align-items-end">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nombre *</label>
                    <input class="form-control" id="name" name="name" placeholder="Ej: Classic" required>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Marca</label>
                    <select class="form-select" id="brand_id" name="brand_id">
                        <option value="">Cargando...</option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Codigo externo</label>
                    <input class="form-control" id="external_code" name="external_code" placeholder="Ej: CLA">
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Activo</label>
                    <select class="form-select" id="is_active" name="is_active">
                        <option value="1">Si</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <div class="col-md-1 mb-3">
                    <button id="saveButton" class="btn btn-primary w-100" type="submit" title="Guardar modelo">
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
            <small class="text-muted">Modelos registrados en el sistema</small>
        </div>

        <input id="search" class="form-control form-control-sm" style="max-width:280px;" placeholder="Buscar modelo...">
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Marca</th>
                    <th>Codigo externo</th>
                    <th class="text-center">Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody id="rows">
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Cargando modelos...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let models = [];
let brands = [];

function statusBadge(value) {
    return value
        ? `<span class="badge bg-label-success">Activo</span>`
        : `<span class="badge bg-label-secondary">Inactivo</span>`;
}

function render(items) {
    if (!items.length) {
        rows.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">No hay modelos registrados</td></tr>`;
        return;
    }

    rows.innerHTML = items.map(item => `
        <tr>
            <td><strong>${item.name ?? '-'}</strong></td>
            <td>${item.brand?.name ?? '-'}</td>
            <td>${item.external_code ?? '-'}</td>
            <td class="text-center">${statusBadge(item.is_active)}</td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-warning" onclick="editModel(${item.id})">
                    Editar
                    <i class="ri-edit-line"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

async function loadBrands() {
    const res = await fetch(`${window.APP_BASE_URL}/api/brands?lookup=1`, { headers: { Accept: 'application/json' } });
    const data = await res.json();
    brands = data.data ?? data;

    document.getElementById('brand_id').innerHTML = '<option value="">Seleccione...</option>';
    brands.forEach(item => {
        document.getElementById('brand_id').innerHTML += `<option value="${item.id}">${item.name ?? item.id}</option>`;
    });
}

async function loadModels() {
    const res = await fetch(`${window.APP_BASE_URL}/api/product-models`, { headers: { Accept: 'application/json' } });
    const data = await res.json();
    models = data.data ?? data;
    render(models);
}

function editModel(id) {
    const item = models.find(model => Number(model.id) === Number(id));
    if (!item) return;

    document.getElementById('name').value = item.name ?? '';
    document.getElementById('brand_id').value = item.brand_id ?? '';
    document.getElementById('external_code').value = item.external_code ?? '';
    document.getElementById('is_active').value = item.is_active ? '1' : '0';
    document.getElementById('modelForm').dataset.editId = item.id;

    saveButton.classList.remove('btn-primary');
    saveButton.classList.add('btn-warning');
    saveButton.innerHTML = 'Actualizar <i class="ri-save-line"></i>';
    saveButton.title = 'Guardar cambios';

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('modelForm').reset();
    delete document.getElementById('modelForm').dataset.editId;
    document.getElementById('is_active').value = '1';
    saveButton.classList.remove('btn-warning');
    saveButton.classList.add('btn-primary');
    saveButton.innerHTML = 'Guardar <i class="ri-add-line"></i>';
    saveButton.title = 'Guardar modelo';
}

document.getElementById('modelForm').addEventListener('submit', async event => {
    event.preventDefault();

    const editId = document.getElementById('modelForm').dataset.editId;
    const payload = {
        name: document.getElementById('name').value.trim(),
        brand_id: document.getElementById('brand_id').value ? Number(document.getElementById('brand_id').value) : null,
        external_code: document.getElementById('external_code').value.trim() || null,
        is_active: document.getElementById('is_active').value === '1'
    };

    if (!payload.name) {
        alert('Ingresa el nombre del modelo.');
        return;
    }

    saveButton.disabled = true;
    saveButton.innerHTML = `Guardando... <span class="spinner-border spinner-border-sm"></span>`;

    try {
        const res = await fetch(editId ? `${window.APP_BASE_URL}/api/product-models/${editId}` : `${window.APP_BASE_URL}/api/product-models`, {
            method: editId ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (!res.ok) {
            const errors = data.errors ? Object.values(data.errors).flat().join('\n') : '';
            alert([data.message ?? 'Error al guardar el modelo.', errors].filter(Boolean).join('\n'));
            return;
        }

        resetForm();
        await loadModels();
    } catch (error) {
        console.error(error);
        alert('No se pudo conectar con el servidor.');
    } finally {
        saveButton.disabled = false;
        if (document.getElementById('modelForm').dataset.editId) {
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
    render(models.filter(item =>
        String(item.name ?? '').toLowerCase().includes(query) ||
        String(item.external_code ?? '').toLowerCase().includes(query) ||
        String(item.brand?.name ?? '').toLowerCase().includes(query)
    ));
});

document.addEventListener('DOMContentLoaded', async () => {
    await loadBrands();
    await loadModels();
});
</script>

@endsection

