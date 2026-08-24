{{-- Vista: Listado de Ventas. Filtra comprobantes y presenta el informe exportable. --}}
@extends('layouts.app')
@section('content')
<div class="mb-4">
    <h4 class="mb-1">Listado de Ventas</h4><small class="text-muted">Detalle consolidado de comprobantes emitidos</small>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form id="filters" class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label">Fecha Desde</label><input id="dateFrom" name="date_from" type="date" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}" required></div>
            <div class="col-md-2"><label class="form-label">Fecha Hasta</label><input id="dateTo" name="date_to" type="date" class="form-control" value="{{ now()->toDateString() }}" required></div>
            <div class="col-md-4"><x-multi-select id="operationTypes" name="operation_types[]" label="Tipo de Operación" /></div>
            @foreach([['branch','Sucursal'],['channel','Canal'],['client_id','Cliente'],['province','Provincia'],['point_of_sale','Punto de Venta'],['seller_id','Vendedor'],['price_type','Lista o Tipo de Precio']] as [$name,$label])
            <div class="col-md-2"><label class="form-label" for="{{ $name }}">{{ $label }}</label><select id="{{ $name }}" name="{{ $name }}" class="form-select">
                    <option value="">Todos</option>
                </select></div>
            @endforeach
            <div class="col-12 d-flex flex-wrap justify-content-end gap-2">
                <button class="btn btn-primary" type="submit"><i class="ri-search-line me-1"></i>Buscar</button>
                <button class="btn btn-secondary" id="clear" type="button"><i class="ri-eraser-line me-1"></i>Limpiar</button>
                <button class="btn btn-success" type="button" data-export="xlsx"><i class="ri-file-excel-2-line me-1"></i>Excel</button>
                <button class="btn btn-outline-success future-action" type="button" data-export="csv"><i class="ri-file-text-line me-1"></i>CSV</button>
                <button class="btn btn-danger" id="openPdf" type="button"><i class="ri-file-pdf-2-line me-1"></i>PDF</button>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h5 class="mb-1">Vista previa del reporte</h5><small id="period" class="text-muted">Seleccioná los filtros y ejecutá la búsqueda</small>
    </div>
    <div class="card-body p-0">
        <div id="empty" class="text-center text-muted py-5"><i class="ri-file-chart-line d-block mb-2" style="font-size:3rem"></i>El reporte PDF se mostrará aquí.</div>
        <iframe id="viewer" title="Listado de Ventas" class="w-100 border-0 d-none" style="min-height:75vh"></iframe>
    </div>
</div>
<script>
    const form = document.getElementById('filters'),
        viewer = document.getElementById('viewer'),
        empty = document.getElementById('empty');
    let pdfUrl = '';
    const operationMulti = erpEnhanceMultiSelect(operationTypes);
    const selects = {
        branch,
        channel,
        client_id,
        province,
        point_of_sale,
        seller_id,
        price_type
    };

    function params() {
        const p = new URLSearchParams(new FormData(form));
        Object.keys(selects).forEach(k => {
            if (!p.get(k)) p.delete(k)
        });
        return p;
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
        pdfUrl = `${window.APP_BASE_URL}/api/reports/sales/list/pdf?${params()}`;
        viewer.src = pdfUrl;
        viewer.classList.remove('d-none');
        empty.classList.add('d-none');
        period.textContent = `${dateFrom.value} al ${dateTo.value}`
    }
    form.addEventListener('submit', e => {
        e.preventDefault();
        load()
    });
    document.getElementById('clear').addEventListener('click', () => {
        form.reset();
        operationMulti.setAll(true);
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
        location.href = `${window.APP_BASE_URL}/api/reports/sales/list/export?${p}`
    }));
    openPdf.addEventListener('click', () => {
        if (valid()) window.open(pdfUrl || `${window.APP_BASE_URL}/api/reports/sales/list/pdf?${params()}`, '_blank', 'noopener')
    });

    function add(select, rows, value = 'value', label = 'label') {
        (rows || []).forEach(r => {
            const object = typeof r === 'object';
            select.add(new Option(object ? r[label] : r, object ? r[value] : r))
        })
    }
    fetch(`${window.APP_BASE_URL}/api/reports/sales/list/options`, {
        headers: {
            Accept: 'application/json'
        }
    }).then(r => r.ok ? r.json() : Promise.reject()).then(d => {
        add(operationTypes, d.operation_types);
        operationMulti.setAll(true);
        add(branch, d.branches);
        add(channel, d.channels);
        add(client_id, d.clients, 'id', 'name');
        add(province, d.provinces);
        add(point_of_sale, d.points_of_sale);
        add(seller_id, d.sellers, 'id', 'name');
        add(price_type, d.price_types);
    }).catch(() => erpAlert('No se pudieron cargar las opciones del reporte.'));
</script>
@endsection