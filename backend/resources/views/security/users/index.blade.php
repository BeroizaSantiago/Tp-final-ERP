{{-- Vista: Listado de Usuarios. Muestra la consulta principal y las acciones disponibles de Usuarios. --}}
@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Usuarios</h4><small class="text-muted">Administración de accesos al sistema</small>
    </div>
    <a href="{{ url('/demo/security/users/create') }}" class="btn btn-primary">
        Nuevo Usuario
    </a>
</div>
<div class="card">
    <div class="card-header"><input id="search" class="form-control" style="max-width:350px" placeholder="Buscar por nombre, usuario o correo..."></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Último acceso</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="rows"></tbody>
        </table>
    </div>
    @include('components.api-pagination')
</div>
<script>
    let timer;
    const searchInput = document.getElementById('search'),
        userRows = document.getElementById('rows'),
        esc = v => String(v ?? '-').replace(/[&<>'"]/g, c => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        } [c]));
    async function load(page = 1) {
        const r = await fetch(`${window.APP_BASE_URL}/api/security/users?page=${page}&search=${encodeURIComponent(searchInput.value)}`),
            j = await r.json();
        userRows.innerHTML = j.data.map(u => `<tr><td><strong>${esc(u.username)}</strong></td><td>${esc([u.first_name,u.last_name].filter(Boolean).join(' ')||u.name)}</td><td>${esc(u.email)}</td><td>${esc(u.role?.name)}</td><td><span class="badge bg-label-${u.is_active?'success':'secondary'}">${u.is_active?'Activo':'Inactivo'}</span></td><td>${u.last_login_at?formatDateTime(u.last_login_at):'Sin accesos'}</td><td class="text-end"><a  class="btn btn-sm btn-warning" href="${window.APP_BASE_URL}/demo/security/users/${u.id}/edit">Editar</a>
        <button type="button" class="btn btn-sm btn-danger" data-toggle-user="${u.id}">${u.is_active?'Desactivar':'Activar'}</button></td></tr>`).join('') || '<tr><td colspan="7" class="text-center py-4">Sin usuarios</td></tr>';
        renderApiPagination(j, load)
    }
    async function toggleUser(id) {
        const r = await fetch(`${window.APP_BASE_URL}/api/security/users/${id}/toggle`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json'
                }
            }),
            j = await r.json();
        if (!r.ok) return Swal.fire({
            icon: 'error',
            text: j.message
        });
        load()
    }
    document.addEventListener('click', e => {
        const toggle = e.target.closest('[data-toggle-user]');
        if (toggle) toggleUser(toggle.dataset.toggleUser);
    });
    searchInput.oninput = () => {
        clearTimeout(timer);
        timer = setTimeout(() => load(), 300)
    };
    load();
</script>
@endsection
