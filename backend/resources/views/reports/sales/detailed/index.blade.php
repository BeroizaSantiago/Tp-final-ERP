{{-- Vista: Listado con Detalle de Ventas. Analiza cada producto vendido. --}}
@extends('layouts.app')
@section('content')
<div class="mb-4">
    <h4 class="mb-1">Listado con Detalle de Ventas</h4><small class="text-muted">Ventas discriminadas por producto e ítem</small>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form id="filters" class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label">Fecha Desde</label><input id="dateFrom" name="date_from" type="date" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}" required></div>
            <div class="col-md-2"><label class="form-label">Fecha Hasta</label><input id="dateTo" name="date_to" type="date" class="form-control" value="{{ now()->toDateString() }}" required></div>
            @foreach([['branch','Sucursal'],['point_of_sale','Punto de Venta'],['client_id','Cliente'],['channel','Canal'],['product_id','Producto'],['provider_id','Proveedor'],['brand_id','Marca'],['model_id','Modelo'],['category_id','Categoría'],['seller_id','Vendedor'],['price_type','Lista o Tipo de Precio']] as [$name,$label])
            <div class="col-md-2"><label class="form-label" for="{{ $name }}">{{ $label }}</label><select id="{{ $name }}" name="{{ $name }}" class="form-select" @if($name==='product_id') data-remote-url="{{ url('/api/products') }}" data-remote-placeholder="Buscar producto (mín. 3 caracteres)" @endif>
                    <option value="">Todos</option>
                </select></div>
            @endforeach
            <div class="col-md-3">
                <div class="form-check mb-2"><input id="withVariant" name="with_variant" value="1" type="checkbox" class="form-check-input"><label class="form-check-label" for="withVariant">Con Variante</label></div><small class="text-muted">Muestra talle, color y SKU.</small>
            </div>
            <div class="col-md-9 d-flex flex-wrap justify-content-md-end gap-2"><button class="btn btn-primary" type="submit"><i class="ri-search-line me-1"></i>Buscar</button><button class="btn btn-secondary" id="clear" type="button"><i class="ri-eraser-line me-1"></i>Limpiar</button><button class="btn btn-success" type="button" data-export="xlsx"><i class="ri-file-excel-2-line me-1"></i>Excel</button><button class="btn btn-outline-success future-action" type="button" data-export="csv"><i class="ri-file-text-line me-1"></i>CSV</button><button class="btn btn-danger" id="openPdf" type="button"><i class="ri-file-pdf-2-line me-1"></i>PDF</button></div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h5 class="mb-1">Vista previa del reporte</h5><small id="period" class="text-muted">Seleccioná los filtros y ejecutá la búsqueda</small>
    </div>
    <div class="card-body p-0">
        <div id="empty" class="text-center text-muted py-5"><i class="ri-file-chart-line d-block mb-2" style="font-size:3rem"></i>El reporte PDF se mostrará aquí.</div><iframe id="viewer" title="Detalle de Ventas" class="w-100 border-0 d-none" style="min-height:75vh"></iframe>
    </div>
</div>
<script>
    const form = document.getElementById('filters'),
        viewer = document.getElementById('viewer'),
        empty = document.getElementById('empty');
    let pdfUrl = '';
    const filterNames = ['branch', 'point_of_sale', 'client_id', 'channel', 'product_id', 'provider_id', 'brand_id', 'model_id', 'category_id', 'seller_id', 'price_type'];

    function params() {
        const p = new URLSearchParams(new FormData(form));
        filterNames.forEach(k => {
            if (!p.get(k)) p.delete(k)
        });
        if (!withVariant.checked) p.set('with_variant', '0');
        return p
    }

    function valid() {
        if (!dateFrom.value || !dateTo.value || dateFrom.value > dateTo.value) {
            erpAlert('Revisá el rango de fechas seleccionado.');
            return false
        }
        return true
    }

    function load() {
        if (!valid()) return;
        pdfUrl = `${window.APP_BASE_URL}/api/reports/sales/detailed/pdf?${params()}`;
        viewer.src = pdfUrl;
        viewer.classList.remove('d-none');
        empty.classList.add('d-none');
        period.textContent = `${dateFrom.value} al ${dateTo.value}${withVariant.checked?' · Con variantes':''}`
    }
    form.addEventListener('submit', e => {
        e.preventDefault();
        load()
    });
    clear.addEventListener('click', () => {
        form.reset();
        viewer.src = 'about:blank';
        viewer.classList.add('d-none');
        empty.classList.remove('d-none');
        period.textContent = 'Seleccioná los filtros y ejecutá la búsqueda';
        pdfUrl = ''
    });
    document.querySelectorAll('[data-export]').forEach(b => b.addEventListener('click', () => {
        if (!valid()) return;
        const p = params();
        p.set('format', b.dataset.export);
        location.href = `${window.APP_BASE_URL}/api/reports/sales/detailed/export?${p}`
    }));
    openPdf.addEventListener('click', () => {
        if (valid()) window.open(pdfUrl || `${window.APP_BASE_URL}/api/reports/sales/detailed/pdf?${params()}`, '_blank', 'noopener')
    });

    function add(id, rows, value = 'value', label = 'label') {
        const el = document.getElementById(id);
        (rows || []).forEach(r => {
            const o = typeof r === 'object';
            el.add(new Option(o ? r[label] : r, o ? r[value] : r))
        })
    }
    fetch(`${window.APP_BASE_URL}/api/reports/sales/detailed/options`, {
        headers: {
            Accept: 'application/json'
        }
    }).then(r => r.ok ? r.json() : Promise.reject()).then(d => {
        add('branch', d.branches);
        add('point_of_sale', d.points_of_sale);
        add('client_id', d.clients, 'id', 'name');
        add('channel', d.channels);
        add('product_id', d.products, 'id', 'name');
        add('provider_id', d.providers, 'id', 'name');
        add('brand_id', d.brands, 'id', 'name');
        add('model_id', d.models, 'id', 'name');
        add('category_id', d.categories, 'id', 'name');
        add('seller_id', d.sellers, 'id', 'name');
        add('price_type', d.price_types)
    }).catch(() => erpAlert('No se pudieron cargar las opciones del reporte.'));
</script>@endsection
