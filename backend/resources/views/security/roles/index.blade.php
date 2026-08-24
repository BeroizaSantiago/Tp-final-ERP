{{-- Vista: Listado de Roles. Muestra la consulta principal y las acciones disponibles de Roles. --}}
@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Roles</h4><small class="text-muted">Administración de perfiles de seguridad</small>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 id="formTitle" class="mb-0">Nuevo rol</h5>
    </div>
    <div class="card-body">
        <form id="roleForm">
            <div class="row align-items-end">
                <div class="col-md-7 mb-3"><label class="form-label">Nombre *</label><input id="roleName" class="form-control" placeholder="Ej: Supervisor" required></div>
                <div class="col-md-3 mb-3"><label class="form-label">Estado</label><select id="roleActive" class="form-select">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select></div>
                <div class="col-md-2 mb-3 d-flex gap-2"><button id="saveButton" class="btn btn-primary flex-grow-1" type="submit">Guardar<i class="ri-add-line ms-1"></i></button><button id="cancelButton" class="btn btn-secondary d-none" type="button">Cancelar</button></div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h5 class="mb-1">Listado</h5><small class="text-muted">Roles registrados en el sistema</small>
        </div><input id="search" class="form-control form-control-sm" style="max-width:280px" placeholder="Buscar rol...">
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Usuarios</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="rows">
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">Cargando roles...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<script>
    let roles = [],
        editingId = null;
    const form = document.getElementById('roleForm'),
        nameInput = document.getElementById('roleName'),
        activeInput = document.getElementById('roleActive'),
        saveButton = document.getElementById('saveButton'),
        cancelButton = document.getElementById('cancelButton'),
        formTitle = document.getElementById('formTitle'),
        rows = document.getElementById('rows'),
        searchInput = document.getElementById('search');
    const esc = v => String(v ?? '').replace(/[&<>'"]/g, c => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#39;',
        '"': '&quot;'
    } [c]));

    function render(items) {
        rows.innerHTML = items.length ? items.map(role => `<tr><td><strong>${esc(role.name)}</strong></td><td class="text-center"><span class="badge bg-label-${role.is_active?'success':'secondary'}">${role.is_active?'Activo':'Inactivo'}</span></td><td class="text-center">${role.users_count}</td><td class="text-end"><button type="button" class="btn btn-sm btn-warning" data-edit="${role.id}">Editar <i class="ri-edit-line"></i></button> <button type="button" class="btn btn-sm btn-danger" data-delete="${role.id}">Eliminar <i class="ri-delete-bin-line"></i></button></td></tr>`).join('') : '<tr><td colspan="4" class="text-center text-muted py-4">No hay roles registrados</td></tr>'
    }
    async function loadRoles() {
        const response = await fetch(`${window.APP_BASE_URL}/api/security/roles`, {
            headers: {
                Accept: 'application/json'
            }
        });
        roles = await response.json();
        filterRoles()
    }

    function filterRoles() {
        const term = searchInput.value.trim().toLowerCase();
        render(roles.filter(role => role.name.toLowerCase().includes(term)))
    }

    function editRole(id) {
        const role = roles.find(item => Number(item.id) === Number(id));
        if (!role) return;
        editingId = role.id;
        nameInput.value = role.name;
        activeInput.value = role.is_active ? '1' : '0';
        formTitle.textContent = 'Editar rol';
        saveButton.innerHTML = 'Actualizar <i class="ri-edit-line ms-1"></i>';
        saveButton.classList.remove('btn-primary');
        saveButton.classList.add('btn-warning');
        cancelButton.classList.remove('d-none');
        nameInput.focus()
    }

    function resetForm() {
        editingId = null;
        form.reset();
        activeInput.value = '1';
        formTitle.textContent = 'Nuevo rol';
        saveButton.innerHTML = 'Guardar<i class="ri-add-line ms-1"></i>';
        saveButton.classList.remove('btn-warning');
        saveButton.classList.add('btn-primary');
        cancelButton.classList.add('d-none')
    }
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const response = await fetch(editingId ? `${window.APP_BASE_URL}/api/security/roles/${editingId}` : `${window.APP_BASE_URL}/api/security/roles`, {
                method: editingId ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify({
                    name: nameInput.value.trim(),
                    is_active: Number(activeInput.value)
                })
            }),
            json = await response.json();
        if (!response.ok) return Swal.fire({
            icon: 'error',
            text: json.message || Object.values(json.errors || {})[0]?.[0]
        });
        resetForm();
        loadRoles()
    });
    cancelButton.addEventListener('click', resetForm);
    searchInput.addEventListener('input', filterRoles);
    rows.addEventListener('click', async event => {
        const edit = event.target.closest('[data-edit]'),
            remove = event.target.closest('[data-delete]');
        if (edit) return editRole(edit.dataset.edit);
        if (!remove) return;
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Eliminar rol',
            text: '¿Querés eliminar este rol?',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar'
        });
        if (!result.isConfirmed) return;
        const response = await fetch(`${window.APP_BASE_URL}/api/security/roles/${remove.dataset.delete}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json'
                }
            }),
            json = await response.json();
        if (!response.ok) return Swal.fire({
            icon: 'error',
            text: json.message
        });
        loadRoles()
    });
    loadRoles();
</script>
@endsection