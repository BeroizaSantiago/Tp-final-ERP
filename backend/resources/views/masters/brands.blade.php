{{-- Vista: Marcas. Muestra la pantalla o componente funcional correspondiente a Marcas. --}}
﻿@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Marcas</h4>
        <small class="text-muted">Administración del catálogo de marcas</small>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Nueva marca</h5>
    </div>

    <div class="card-body">
        <form id="brandForm">
            <div class="row align-items-end">

                <div class="col-md-5 mb-3">
                    <label class="form-label">Nombre *</label>
                    <input
                        class="form-control"
                        id="name"
                        name="name"
                        placeholder="Ej: Adidas"
                        required
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Código externo</label>
                    <input
                        class="form-control"
                        id="external_code"
                        name="external_code"
                        placeholder="Ej: ADI"
                    >
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Activo</label>

                    <select
                        class="form-select"
                        id="is_active"
                        name="is_active"
                    >
                        <option value="1">Sí</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <div class="col-md-1 mb-3">
                    <button
                        id="saveButton"
                        class="btn btn-primary w-100"
                        type="submit"
                        title="Guardar marca"
                    >
                   Guardar
                        <i class="ri-add-line"></i>
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
            <small class="text-muted">Marcas registradas en el sistema</small>
        </div>

        <input
            id="search"
            class="form-control form-control-sm"
            style="max-width:280px;"
            placeholder="Buscar marca..."
        >
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Código externo</th>
                    <th class="text-center">Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody id="rows">
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        Cargando marcas...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let brands = [];

function statusBadge(value) {
    return value
        ? `<span class="badge bg-label-success">Activo</span>`
        : `<span class="badge bg-label-secondary">Inactivo</span>`;
}

function render(items) {
    if (!items.length) {
        rows.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-muted py-4">
                    No hay marcas registradas
                </td>
            </tr>
        `;
        return;
    }

    rows.innerHTML = items.map(item => `
        <tr>
            <td>
                <strong>${item.name ?? '-'}</strong>
            </td>

            <td>
                ${item.external_code ?? '-'}
            </td>

            <td class="text-center">
                ${statusBadge(item.is_active)}
            </td>

            <td class="text-end">
                <button
                    type="button"
                    class="btn btn-sm btn-warning"
                    onclick="editBrand(${item.id})"
                >
                Editar
                    <i class="ri-edit-line"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

async function loadBrands() {
    const res = await fetch(`${window.APP_BASE_URL}/api/brands`, {
        headers: {
            Accept: 'application/json'
        }
    });

    const data = await res.json();

    brands = data.data ?? data;
    render(brands);
}

function editBrand(id) {
    const brand = brands.find(item => Number(item.id) === Number(id));

    if (!brand) {
        return;
    }

    document.getElementById('name').value = brand.name ?? '';
    document.getElementById('external_code').value = brand.external_code ?? '';
    document.getElementById('is_active').value = brand.is_active ? '1' : '0';

    document.getElementById('brandForm').dataset.editId = brand.id;

    const button = document.getElementById('saveButton');

    button.classList.remove('btn-primary');
    button.classList.add('btn-warning');
    button.innerHTML = 'Actualizar <i class="ri-save-line"></i>';
    button.title = 'Guardar cambios';

    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

function resetForm() {
    const form = document.getElementById('brandForm');

    form.reset();
    delete form.dataset.editId;

    document.getElementById('is_active').value = '1';

    const button = document.getElementById('saveButton');

    button.classList.remove('btn-warning');
    button.classList.add('btn-primary');
    button.innerHTML = 'Guardar <i class="ri-add-line"></i>';
    button.title = 'Guardar marca';
}

document
    .getElementById('brandForm')
    .addEventListener('submit', async event => {
        event.preventDefault();

        const form = event.target;
        const editId = form.dataset.editId;

        const payload = {
            name: document.getElementById('name').value.trim(),
            external_code:
                document.getElementById('external_code').value.trim() || null,
            is_active:
                document.getElementById('is_active').value === '1'
        };

        if (!payload.name) {
            alert('Ingresá el nombre de la marca.');
            return;
        }

        const url = editId
            ? `${window.APP_BASE_URL}/api/brands/${editId}`
            : `${window.APP_BASE_URL}/api/brands`;

        const method = editId
            ? 'PUT'
            : 'POST';

        const button = document.getElementById('saveButton');

        button.disabled = true;
        button.innerHTML = `
            Guardando...
            <span class="spinner-border spinner-border-sm"></span>
        `;

        try {
            const res = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (!res.ok) {
                const errors = data.errors
                    ? Object.values(data.errors).flat().join('\n')
                    : '';

                alert([
                    data.message ?? 'Error al guardar la marca.',
                    errors
                ].filter(Boolean).join('\n'));

                return;
            }

            resetForm();
            await loadBrands();

        } catch (error) {
            console.error(error);
            alert('No se pudo conectar con el servidor.');
        } finally {
            button.disabled = false;

            if (form.dataset.editId) {
                button.classList.remove('btn-primary');
                button.classList.add('btn-warning');
                button.innerHTML = 'Actualizar <i class="ri-save-line"></i>';
            } else {
                button.classList.remove('btn-warning');
                button.classList.add('btn-primary');
                button.innerHTML = 'Guardar <i class="ri-add-line"></i>';
            }
        }
    });

document
    .getElementById('search')
    .addEventListener('input', event => {
        const query = event.target.value.toLowerCase().trim();

        render(
            brands.filter(item =>
                String(item.name ?? '')
                    .toLowerCase()
                    .includes(query) ||
                String(item.external_code ?? '')
                    .toLowerCase()
                    .includes(query)
            )
        );
    });

document.addEventListener('DOMContentLoaded', loadBrands);
</script>

@endsection
