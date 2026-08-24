{{-- Vista: Categorías de Producto. Muestra la pantalla o componente funcional correspondiente a Categorías de Producto. --}}
﻿@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Categorias de Productos</h4>
        <small class="text-muted">Administracion del catalogo de categorias</small>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Nueva categoria</h5>
    </div>

    <div class="card-body">
        <form id="categoryForm">
            <div class="row align-items-end">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nombre *</label>
                    <input class="form-control" id="name" name="name" placeholder="Ej: Indumentaria" required>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Codigo externo</label>
                    <input class="form-control" id="external_code" name="external_code" placeholder="Ej: IND">
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Orden web</label>
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
                    <button id="saveButton" class="btn btn-primary w-100" type="submit" title="Guardar categoria">
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
            <small class="text-muted">Categorias registradas en el sistema</small>
        </div>

        <input id="search" class="form-control form-control-sm" style="max-width:280px;" placeholder="Buscar categoria...">
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Codigo externo</th>
                    <th class="text-end">Orden</th>
                    <th class="text-center">Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody id="rows">
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Cargando categorias...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let categories = [];

function statusBadge(value) {
    return value
        ? `<span class="badge bg-label-success">Activo</span>`
        : `<span class="badge bg-label-secondary">Inactivo</span>`;
}

function render(items) {
    if (!items.length) {
        rows.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">No hay categorias registradas</td></tr>`;
        return;
    }

    rows.innerHTML = items.map(item => `
        <tr>
            <td><strong>${item.name ?? '-'}</strong></td>
            <td>${item.external_code ?? '-'}</td>
            <td class="text-end">${item.web_order ?? '-'}</td>
            <td class="text-center">${statusBadge(item.is_active)}</td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-warning" onclick="editCategory(${item.id})">
                    Editar
                    <i class="ri-edit-line"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

async function loadCategories() {
    const res = await fetch(`${window.APP_BASE_URL}/api/product-categories`, { headers: { Accept: 'application/json' } });
    const data = await res.json();
    categories = data.data ?? data;
    render(categories);
}

function editCategory(id) {
    const category = categories.find(item => Number(item.id) === Number(id));
    if (!category) return;

    document.getElementById('name').value = category.name ?? '';
    document.getElementById('external_code').value = category.external_code ?? '';
    document.getElementById('web_order').value = category.web_order ?? 1;
    document.getElementById('is_active').value = category.is_active ? '1' : '0';
    document.getElementById('categoryForm').dataset.editId = category.id;

    saveButton.classList.remove('btn-primary');
    saveButton.classList.add('btn-warning');
    saveButton.innerHTML = 'Actualizar <i class="ri-save-line"></i>';
    saveButton.title = 'Guardar cambios';

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('categoryForm').reset();
    delete document.getElementById('categoryForm').dataset.editId;
    document.getElementById('web_order').value = 1;
    document.getElementById('is_active').value = '1';
    saveButton.classList.remove('btn-warning');
    saveButton.classList.add('btn-primary');
    saveButton.innerHTML = 'Guardar <i class="ri-add-line"></i>';
    saveButton.title = 'Guardar categoria';
}

document.getElementById('categoryForm').addEventListener('submit', async event => {
    event.preventDefault();

    const editId = document.getElementById('categoryForm').dataset.editId;
    const payload = {
        name: document.getElementById('name').value.trim(),
        external_code: document.getElementById('external_code').value.trim() || null,
        web_order: Number(document.getElementById('web_order').value || 1),
        is_active: document.getElementById('is_active').value === '1'
    };

    if (!payload.name) {
        alert('Ingresa el nombre de la categoria.');
        return;
    }

    saveButton.disabled = true;
    saveButton.innerHTML = `Guardando... <span class="spinner-border spinner-border-sm"></span>`;

    try {
        const res = await fetch(editId ? `${window.APP_BASE_URL}/api/product-categories/${editId}` : `${window.APP_BASE_URL}/api/product-categories`, {
            method: editId ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (!res.ok) {
            const errors = data.errors ? Object.values(data.errors).flat().join('\n') : '';
            alert([data.message ?? 'Error al guardar la categoria.', errors].filter(Boolean).join('\n'));
            return;
        }

        resetForm();
        await loadCategories();
    } catch (error) {
        console.error(error);
        alert('No se pudo conectar con el servidor.');
    } finally {
        saveButton.disabled = false;
        if (document.getElementById('categoryForm').dataset.editId) {
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
    render(categories.filter(item =>
        String(item.name ?? '').toLowerCase().includes(query) ||
        String(item.external_code ?? '').toLowerCase().includes(query)
    ));
});

document.addEventListener('DOMContentLoaded', loadCategories);
</script>

@endsection

