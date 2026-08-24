{{-- Vista: Detalle de Promociones de Venta. Muestra la información completa de un registro de Promociones de Venta. --}}
@extends('layouts.app')
@section('content')
<div class="d-flex gap-2 mb-4"><a href="{{ url('/demo/sales-promotions') }}" class="btn btn-secondary">Volver</a><a href="{{ url('/demo/sales-promotions') }}/{{$promotionId}}/edit" class="btn btn-primary">Editar</a></div>
<div id="detail" class="card">
    <div class="card-body text-center py-5">Cargando...</div>
</div>
<script>
    const id = @json($promotionId),
        names = {
            all: 'Todos los productos',
            product: 'Productos',
            category: 'Categorías',
            brand: 'Marcas'
        };
    fetch(`${window.APP_BASE_URL}/api/sales-promotions/` + id).then(r => r.json()).then(x => {
        const days = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        detail.innerHTML = `<div class="card-header d-flex justify-content-between"><h4 class="mb-0">${x.name}</h4><span class="badge ${x.is_active?'bg-success':'bg-secondary'}">${x.is_active?'Activa':'Inactiva'}</span></div><div class="card-body"><div class="row g-4"><div class="col-md-3"><small class="text-muted">Vigencia</small><div>${x.date_from?formatDateTime(x.date_from):'Sin inicio'} — ${x.date_to?formatDateTime(x.date_to):'Sin vencimiento'}</div></div><div class="col-md-3"><small class="text-muted">Días</small><div>${x.active_weekdays?.length?x.active_weekdays.map(n=>days[n-1]).join(', '):'Todos'}</div></div><div class="col-md-3"><small class="text-muted">Moneda / Lista</small><div>${x.currency_name??'Todas'} · ${x.price_list_name??'Todas'}</div></div><div class="col-md-3"><small class="text-muted">Empresa / Sucursal</small><div>${x.company_name??'Todas'} · ${x.branch_name??'Todas'}</div></div><div class="col-md-3"><small class="text-muted">Beneficio</small><div>${x.discount_type} · ${x.discount_value}</div></div><div class="col-md-3"><small class="text-muted">Alcance</small><div>${names[x.applies_to]}</div></div><div class="col-md-3"><small class="text-muted">Visualización</small><div>${x.display_mode==='line'?'Por producto':'Global'}</div></div><div class="col-md-3"><small class="text-muted">Medios de pago</small><div>${x.payment_methods?.length?x.payment_methods.join(', '):'Todos'}</div></div></div>${x.items?.length?`<hr><h5>Elementos alcanzados</h5><div>${x.items.map(i=>`<span class="badge bg-label-primary me-2">${i.product?.name??i.category?.name??i.brand?.name??'-'}</span>`).join('')}</div>`:''}</div>`
    });
</script>
@endsection