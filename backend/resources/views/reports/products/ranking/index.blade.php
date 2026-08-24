{{-- Vista: Ranking de Productos Vendidos. Ordena ventas netas según el criterio elegido. --}}
@extends('layouts.app')
@section('content')
<div class="mb-4">
    <h4 class="mb-1">Ranking de Productos Vendidos</h4><small class="text-muted">Productos con mayor participación según ventas netas</small>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form id="filters" class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label">Fecha Desde</label><input id="dateFrom" name="date_from" type="date" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}" required></div>
            <div class="col-md-2"><label class="form-label">Fecha Hasta</label><input id="dateTo" name="date_to" type="date" class="form-control" value="{{ now()->toDateString() }}" required></div>
            <div class="col-md-2"><label class="form-label">Listar por</label><select name="list_by" class="form-select">
                    <option value="product">Producto</option>
                    <option value="category">Categoría</option>
                    <option value="brand">Marca</option>
                    <option value="model">Modelo</option>
                    <option value="provider">Proveedor</option>
                </select></div>
            <div class="col-md-2"><label class="form-label">Top</label><input name="top" type="number" min="1" max="500" value="10" list="topOptions" class="form-control"><datalist id="topOptions">
                    <option value="5">
                    <option value="10">
                    <option value="25">
                    <option value="50">
                    <option value="100">
                    <option value="250">
                    <option value="500">
                </datalist></div>
            <div class="col-md-2"><label class="form-label">Ordenar por</label><select name="order_by" class="form-select">
                    <option value="units">Unidades vendidas</option>
                    <option value="total_sale">Total vendido</option>
                    <option value="operations">Operaciones</option>
                    <option value="profit">Ganancia</option>
                </select></div>
            @foreach([['category_id','Categoría'],['color','Color'],['provider_id','Proveedor'],['brand_id','Marca'],['model_id','Modelo']] as [$name,$label])<div class="col-md-2"><label class="form-label">{{ $label }}</label><select id="{{ $name }}" name="{{ $name }}" class="form-select">
                    <option value="">Todos</option>
                </select></div>@endforeach
            <div class="col-md-2"><label class="form-label">Talle</label><input id="size" name="size" list="sizeOptions" class="form-control" placeholder="Seleccionar o escribir..."><datalist id="sizeOptions"></datalist></div>
            <div class="col-md-2">
                <div class="form-check mb-2"><input id="withVariant" name="with_variant" value="1" class="form-check-input" type="checkbox"><label class="form-check-label" for="withVariant">Agregar variantes</label></div>
            </div>
            <div class="col-md-8 d-flex justify-content-end flex-wrap gap-2"><button class="btn btn-primary" type="submit">Buscar</button><button id="clear" class="btn btn-secondary" type="button">Limpiar</button><button class="btn btn-success" data-export="xlsx" type="button">Excel</button><button class="btn btn-outline-success future-action" data-export="csv" type="button">CSV</button><button id="openPdf" class="btn btn-danger" type="button">PDF</button></div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h5 class="mb-1">Vista previa</h5><small id="period" class="text-muted">Seleccioná los filtros y ejecutá la búsqueda</small>
    </div>
    <div class="card-body p-0">
        <div id="empty" class="text-center text-muted py-5">El reporte PDF se mostrará aquí.</div><iframe id="viewer" class="w-100 border-0 d-none" style="min-height:75vh" title="Ranking de Productos Vendidos"></iframe>
    </div>
</div>
<script>
    const form = document.getElementById('filters'),
        viewer = document.getElementById('viewer'),
        empty = document.getElementById('empty');
    let pdfUrl = '';
    const optional = ['category_id', 'color', 'provider_id', 'brand_id', 'model_id', 'size'];

    function params() {
        const p = new URLSearchParams(new FormData(form));
        optional.forEach(k => {
            if (!p.get(k)) p.delete(k)
        });
        if (!withVariant.checked) p.set('with_variant', '0');
        return p
    }

    function valid() {
        if (!dateFrom.value || !dateTo.value || dateFrom.value > dateTo.value) {
            erpAlert('Revisá el rango de fechas.');
            return false
        }
        return true
    }

    function load() {
        if (!valid()) return;
        pdfUrl = `${window.APP_BASE_URL}/api/reports/products/ranking/pdf?${params()}`;
        viewer.src = pdfUrl;
        viewer.classList.remove('d-none');
        empty.classList.add('d-none');
        period.textContent = `${dateFrom.value} al ${dateTo.value}`
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
        pdfUrl = ''
    });
    document.querySelectorAll('[data-export]').forEach(b => b.addEventListener('click', () => {
        if (!valid()) return;
        const p = params();
        p.set('format', b.dataset.export);
        location.href = `${window.APP_BASE_URL}/api/reports/products/ranking/export?${p}`
    }));
    openPdf.addEventListener('click', () => {
        if (valid()) window.open(pdfUrl || `${window.APP_BASE_URL}/api/reports/products/ranking/pdf?${params()}`, '_blank', 'noopener')
    });

    function add(id, rows) {
        const el = document.getElementById(id);
        (rows || []).forEach(v => el.add(new Option(typeof v === 'object' ? v.name : v, typeof v === 'object' ? v.id : v)))
    }
    fetch(`${window.APP_BASE_URL}/api/reports/products/ranking/options`, {
        headers: {
            Accept: 'application/json'
        }
    }).then(r => r.json()).then(d => {
        add('category_id', d.categories);
        add('color', d.colors);
        add('provider_id', d.providers);
        add('brand_id', d.brands);
        add('model_id', d.models);
        (d.sizes || []).forEach(v => sizeOptions.append(new Option(v, v)))
    }).catch(() => erpAlert('No se pudieron cargar los filtros.'));
</script>
@endsection