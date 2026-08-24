{{-- Vista: Usuarios. Muestra la pantalla o componente funcional correspondiente a Usuarios. --}}
@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 id="title">Nuevo Usuario</h4><small class="text-muted">Los campos marcados con * son obligatorios</small>
    </div>
    <a href="{{ url('/demo/security/users') }}" class="btn btn-outline-primary">
        Volver
    </a>
</div>
<form id="form">
    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                @foreach([['username','Nombre de Usuario *','text'],['first_name','Nombres *','text'],['last_name','Apellido *','text'],['email','Email *','email'],['password','Contraseña *','password'],['password_confirmation','Confirmar Contraseña *','password'],['birth_date','Fecha Nacimiento','date'],['hire_date','Fecha Ingreso','date'],['phone','Teléfono','text'],['country','País','text'],['province','Provincia','text'],['address','Dirección','text'],['neighborhood','Barrio','text']] as $f)<div class="col-md-4"><label class="form-label">{{$f[1]}}</label><input name="{{$f[0]}}" type="{{$f[2]}}" class="form-control" @if(str_contains($f[1],'*')) required @endif @if(in_array($f[0],['country','province'])) placeholder="Ingrese 3 caracteres" @endif></div>@endforeach
                <div class="col-md-4"><label class="form-label">Rol *</label><select name="role_id" class="form-select" required></select></div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check form-switch mb-2"><input name="is_active" type="checkbox" class="form-check-input" checked><label class="form-check-label">Usuario activo</label></div>
                </div>
                @foreach([['max_discount_percentage','Máximo Descuento Permitido (%)'],['sales_commission_percentage','Comisión Por Venta (%)'],['collections_commission_percentage','Comisión por Cobranza (%)'],['profit_commission_percentage','Comisión por Ganancia (%)']] as $f)<div class="col-md-3"><label class="form-label">{{$f[1]}} <small class="text-muted">(opcional)</small></label><input name="{{$f[0]}}" type="number" min="0" max="100" step="0.01" class="form-control"></div>@endforeach
                <div id="error" class="col-12 text-danger"></div>
                <div class="col-12 text-end"><button class="btn btn-primary">Guardar Usuario</button></div>
            </div>
        </div>
    </div>
</form>
<script>
    const userId = @json($userId ?? null),
        form = document.getElementById('form'),
        roleSelect = form.elements['role_id'],
        activeInput = form.elements['is_active'],
        passwordInput = form.elements['password'],
        confirmationInput = form.elements['password_confirmation'],
        errorBox = document.getElementById('error');
    async function init() {
        const roles = await fetch(`${window.APP_BASE_URL}/api/security/roles`).then(r => r.json());
        roleSelect.innerHTML = roles.filter(r => r.is_active).map(r => `<option value="${r.id}">${r.name}</option>`).join('');
        if (userId) {
            document.getElementById('title').textContent = 'Editar Usuario';
            const u = await fetch(`${window.APP_BASE_URL}/api/security/users/${userId}`).then(r => r.json());
            Object.keys(u).forEach(k => {
                if (form.elements[k] && u[k] != null) form.elements[k].value = String(u[k]).slice(0, 10)
            });
            activeInput.checked = u.is_active;
            passwordInput.required = false;
            confirmationInput.required = false
        }
    }
    form.onsubmit = async e => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(form));
        data.is_active = activeInput.checked ? 1 : 0;
        Object.keys(data).forEach(k => data[k] === '' && delete data[k]);
        const r = await fetch(userId ? `${window.APP_BASE_URL}/api/security/users/${userId}` : `${window.APP_BASE_URL}/api/security/users`, {
                method: userId ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify(data)
            }),
            j = await r.json();
        if (!r.ok) {
            errorBox.textContent = j.message || Object.values(j.errors || {})[0]?.[0];
            return
        }
        location = `${window.APP_BASE_URL}/demo/security/users`
    };
    init();
</script>
@endsection
