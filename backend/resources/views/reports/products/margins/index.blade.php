{{-- Vista compartida: márgenes por producto, categoría o marca. --}}
@extends('layouts.app')
@php($titles=['product'=>'Margen por Producto','category'=>'Margen por Categoría','brand'=>'Margen por Marca'])
@php($labels=['product'=>'Producto','category'=>'Categoría','brand'=>'Marca'])
@section('content')
<div class="mb-4">
    <h4 class="mb-1">{{ $titles[$grouping] }}</h4><small class="text-muted">Rentabilidad obtenida desde ventas y costos históricos</small>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form id="filters" class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label">Fecha Desde</label><input id="dateFrom" name="date_from" type="date" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}" required></div>
            <div class="col-md-2"><label class="form-label">Fecha Hasta</label><input id="dateTo" name="date_to" type="date" class="form-control" value="{{ now()->toDateString() }}" required></div>
            <div class="col-md-3"><label class="form-label">Sucursal</label><select id="branch" name="branch" class="form-select">
                    <option value="">Todas</option>
                </select></div>
            <div class="col-md-3"><label class="form-label">{{ $labels[$grouping] }}</label><select id="mainFilter" name="{{ $grouping }}_id" class="form-select" @if($grouping==='product') data-remote-url="{{ url('/api/products') }}" data-remote-placeholder="Buscar producto (mín. 3 caracteres)" @endif>
                    <option value="">Todos</option>
                </select></div>
            <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary" type="submit">Buscar</button><button id="clear" class="btn btn-secondary" type="button">Limpiar</button></div>
        </form>
    </div>
</div>
<div class="col-12 d-flex justify-content-end gap-2"><button class="btn btn-success" data-export="xlsx" type="button">Excel</button><button class="btn btn-outline-success future-action" data-export="csv" type="button">CSV</button><button id="openPdf" class="btn btn-danger" type="button">PDF</button></div>
<div class="card">

    <div class="card-header">
        <h5 class="mb-1">Vista previa</h5><small id="period" class="text-muted">Seleccioná los filtros y ejecutá la búsqueda</small>
    </div>
    <div class="card-body p-0">
        <div id="empty" class="text-center text-muted py-5">El reporte PDF se mostrará aquí.</div><iframe id="viewer" class="w-100 border-0 d-none" style="min-height:75vh" title="{{ $titles[$grouping] }}"></iframe>
    </div>
</div>
<script>
    const grouping = @json($grouping),
        form = document.getElementById('filters'),
        viewer = document.getElementById('viewer'),
        empty = document.getElementById('empty');
    let pdfUrl = '';

    function params() {
        const p = new URLSearchParams(new FormData(form));
        if (!branch.value) p.delete('branch');
        if (!mainFilter.value) p.delete(`${grouping}_id`);
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
        pdfUrl = `${window.APP_BASE_URL}/api/reports/products/margins/${grouping}/pdf?${params()}`;
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
        location.href = `${window.APP_BASE_URL}/api/reports/products/margins/${grouping}/export?${p}`
    }));
    openPdf.addEventListener('click', () => {
        if (valid()) window.open(pdfUrl || `${window.APP_BASE_URL}/api/reports/products/margins/${grouping}/pdf?${params()}`, '_blank', 'noopener')
    });
    fetch(`${window.APP_BASE_URL}/api/reports/products/margins/options`, {
        headers: {
            Accept: 'application/json'
        }
    }).then(r => r.json()).then(d => {
        (d.branches || []).forEach(v => branch.add(new Option(v, v)));
        const source = d[grouping === 'product' ? 'products' : grouping === 'category' ? 'categories' : 'brands'] || [];
        source.forEach(v => mainFilter.add(new Option(v.name, v.id)))
    }).catch(() => erpAlert('No se pudieron cargar los filtros.'));
</script>
@endsection
