{{-- Vista: Promociones de Venta. Muestra la pantalla o componente funcional correspondiente a Promociones de Venta. --}}
@extends('layouts.app')
@section('content')
@php($promotionId = $promotionId ?? null)
<a href="{{ url('/demo/sales-promotions') }}" class="btn btn-secondary mb-3">Volver</a>
<div class="mb-4">
    <h4>{{ $promotionId ? 'Editar' : 'Nueva' }} Promoción de Venta</h4><small class="text-muted">La promoción se ofrecerá al vendedor cuando sea compatible</small>
</div>
<form id="promotionForm">
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Datos generales</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Nombre</label><input id="name" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Tipo de promoción</label><select id="promotion_type" class="form-select">
                        <option value="discount">Descuento</option>
                        <option value="special_price">Precio especial</option>
                        <option value="combo">Combo</option>
                    </select></div>
                <div class="col-md-3"><label class="form-label">Válida desde</label><input id="date_from" type="date" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Válida hasta</label><input id="date_to" type="date" class="form-control"><small class="text-muted">Vacío: sin vencimiento</small></div>
                <div class="col-12"><label class="form-label d-block">Días activos</label>
                    <div class="d-flex flex-wrap gap-3">@foreach([1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'] as $number=>$day)<label class="form-check"><input class="form-check-input weekday" type="checkbox" value="{{$number}}"><span class="form-check-label">{{$day}}</span></label>@endforeach</div><small class="text-muted">Sin selección: todos los días</small>
                </div>
                <div class="col-md-3"><label class="form-label">Moneda</label><select id="currency_name" class="form-select">
                        <option value="">Todas</option>
                        <option>Pesos</option>
                        <option>Dólares</option>
                    </select></div>
                <div class="col-md-3"><label class="form-label">Empresa</label><input id="company_name" class="form-control" placeholder="Todas"></div>
                <div class="col-md-3"><label class="form-label">Sucursal</label><select id="branch_name" class="form-select" data-stock-branch data-stock-allow-empty></select></div>
                <div class="col-md-3"><label class="form-label">Lista de precios</label><select id="price_list_name" class="form-select">
                        <option value="">Todas</option>
                        <option>PRECIO A</option>
                        <option>PRECIO B</option>
                        <option>PRECIO C</option>
                        <option>PRECIO D</option>
                    </select></div>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Beneficio y alcance</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Tipo de descuento</label><select id="discount_type" class="form-select">
                        <option value="percentage">Porcentaje</option>
                        <option value="fixed_amount">Importe fijo</option>
                        <option value="special_price">Precio especial</option>
                        <option value="combo">Combo</option>
                    </select></div>
                <div class="col-md-2"><label class="form-label">Valor</label><input id="discount_value" type="number" min="0" step="0.01" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Visualización</label><select id="display_mode" class="form-select">
                        <option value="line">En cada producto</option>
                        <option value="global">Descuento global</option>
                    </select></div>
                <div class="col-md-2"><label class="form-label">Importe mínimo</label><input id="minimum_amount" type="number" min="0" step="0.01" value="0" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Cantidad mínima</label><input id="minimum_quantity" type="number" min="0" step="0.01" value="0" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Alcance</label><select id="applies_to" class="form-select">
                        <option value="all">Todos los productos</option>
                        <option value="product">Productos seleccionados</option>
                        <option value="category">Categorías seleccionadas</option>
                        <option value="brand">Marcas seleccionadas</option>
                    </select></div>
                <div class="col-md-8" id="scopeBox"></div>
                <div class="col-12"><label class="form-label d-block">Medios de pago compatibles</label>
                    <div class="d-flex flex-wrap gap-3">@foreach(['cash'=>'Efectivo','credit_card'=>'Tarjeta crédito','debit_card'=>'Tarjeta débito','transfer'=>'Transferencia','current_account'=>'Cuenta corriente'] as $key=>$label)<label class="form-check"><input class="form-check-input payment" type="checkbox" value="{{$key}}"><span class="form-check-label">{{$label}}</span></label>@endforeach</div><small class="text-muted">Sin selección: todos los medios</small>
                </div>
                <div class="col-12"><label class="form-check form-switch"><input id="is_active" class="form-check-input" type="checkbox" checked><span class="form-check-label">Promoción activa</span></label></div>
            </div>
        </div>
    </div>
    <div id="formError" class="alert alert-danger d-none"></div>
    <div class="text-end"><button class="btn btn-primary">Guardar promoción</button></div>
</form>
<script>
    const paymentMethodsContainer = document.querySelector('.payment')?.closest('.d-flex.flex-wrap.gap-3');
    paymentMethodsContainer?.insertAdjacentHTML('beforeend', '<label class="form-check"><input class="form-check-input payment" type="checkbox" value="mercado_pago_qr"><span class="form-check-label">QR</span></label>');
    const promotionId = @json($promotionId),
        masters = {
            product: [],
            category: [],
            brand: []
        };
    let existingItems = [];
    async function loadMasters() {
        const [p, c, b] = await Promise.all([`${window.APP_BASE_URL}/api/products?per_page=100`, `${window.APP_BASE_URL}/api/product-categories?lookup=1`, `${window.APP_BASE_URL}/api/brands?lookup=1`].map(u => fetch(u).then(r => r.json())));
        masters.product = p.data ?? p;
        masters.category = c.data ?? c;
        masters.brand = b.data ?? b;
        renderScope()
    }

    function renderScope() {
        const type = applies_to.value;
        if (type === 'all') {
            scopeBox.innerHTML = '<div class="text-muted pt-4">La promoción alcanza todos los productos.</div>';
            return
        }
        const labels = {
            product: 'Productos',
            category: 'Categorías',
            brand: 'Marcas'
        };
        scopeBox.innerHTML = `<label class="form-label">${labels[type]}</label><select id="scopeValues" class="form-select" multiple size="5">${masters[type].map(x=>`<option value="${x.id}" ${existingItems.some(i=>Number(i[type+'_id'])===Number(x.id))?'selected':''}>${x.name}</option>`).join('')}</select><small class="text-muted">Podés seleccionar más de uno con Ctrl.</small>`
    }
    applies_to.onchange = () => {
        existingItems = [];
        renderScope()
    };

    function value(id) {
        return document.getElementById(id).value || null
    }

    function payload() {
        const type = applies_to.value;
        return {
            name: value('name'),
            promotion_type: value('promotion_type'),
            date_from: value('date_from'),
            date_to: value('date_to'),
            active_weekdays: [...document.querySelectorAll('.weekday:checked')].map(x => Number(x.value)),
            currency_name: value('currency_name'),
            company_name: value('company_name'),
            branch_name: value('branch_name'),
            price_list_name: value('price_list_name'),
            payment_methods: [...document.querySelectorAll('.payment:checked')].map(x => x.value),
            discount_type: value('discount_type'),
            discount_value: value('discount_value'),
            display_mode: value('display_mode'),
            minimum_amount: value('minimum_amount'),
            minimum_quantity: value('minimum_quantity'),
            applies_to: type,
            is_active: is_active.checked,
            items: type === 'all' ? [] : [...(document.getElementById('scopeValues')?.selectedOptions ?? [])].map(x => ({
                [type + '_id']: Number(x.value)
            }))
        }
    }

    function fill(x) {
        branch_name.dataset.selected = x.branch_name ?? '';
        ['name', 'promotion_type', 'date_from', 'date_to', 'currency_name', 'company_name', 'branch_name', 'price_list_name', 'discount_type', 'discount_value', 'display_mode', 'minimum_amount', 'minimum_quantity', 'applies_to'].forEach(id => document.getElementById(id).value = x[id] ?? '');
        is_active.checked = x.is_active;
        document.querySelectorAll('.weekday').forEach(i => i.checked = (x.active_weekdays ?? []).map(Number).includes(Number(i.value)));
        document.querySelectorAll('.payment').forEach(i => i.checked = (x.payment_methods ?? []).includes(i.value));
        existingItems = x.items ?? [];
        renderScope()
    }
    promotionForm.onsubmit = async e => {
        e.preventDefault();
        const r = await fetch(promotionId ? `${window.APP_BASE_URL}/api/sales-promotions/${promotionId}` : `${window.APP_BASE_URL}/api/sales-promotions`, {
            method: promotionId ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json'
            },
            body: JSON.stringify(payload())
        });
        const d = await r.json();
        if (!r.ok) {
            formError.classList.remove('d-none');
            formError.textContent = d.message ?? 'No se pudo guardar';
            return
        }
        alert('Promoción guardada correctamente.');
        location.href = "{{ url('/demo/sales-promotions') }}"
    };
    (async () => {
        await loadMasters();
        if (promotionId) fill(await fetch(`${window.APP_BASE_URL}/api/sales-promotions/` + promotionId).then(r => r.json()))
    })();
</script>
@endsection
