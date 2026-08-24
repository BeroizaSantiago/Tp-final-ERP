{{-- Vista: Reporte de Ventas por Fechas y Cajas. Audita ingresos por caja y cajero. --}}
@extends('layouts.app')
@section('content')
<div class="mb-4">
    <h4 class="mb-1">Reporte de Ventas por Fechas y Cajas</h4><small class="text-muted">Ingresos agrupados por jornada, caja, cajero y medio de pago</small>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form id="filters" class="row g-3 align-items-end">
            <div class="col-md-3"><label class="form-label">Fecha Desde</label><input id="dateFrom" name="date_from" type="date" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}" required></div>
            <div class="col-md-3"><label class="form-label">Fecha Hasta</label><input id="dateTo" name="date_to" type="date" class="form-control" value="{{ now()->toDateString() }}" required></div>
            <div class="col-md-4"><x-multi-select id="cashBoxes" name="cash_box_ids[]" label="Cajas" help="Sin selección incluye todas las cajas y registros históricos sin caja." /></div>
            <div class="col-md-2 d-flex flex-wrap gap-2"><button class="btn btn-primary" type="submit"><i class="ri-search-line me-1"></i>Buscar</button><button class="btn btn-secondary" id="clear" type="button"><i class="ri-eraser-line me-1"></i>Limpiar</button></div>
            <div class="col-12 d-flex justify-content-end flex-wrap gap-2"><button class="btn btn-success" type="button" data-export="xlsx"><i class="ri-file-excel-2-line me-1"></i>Excel</button><button class="btn btn-outline-success future-action" type="button" data-export="csv"><i class="ri-file-text-line me-1"></i>CSV</button><button class="btn btn-danger" id="openPdf" type="button"><i class="ri-file-pdf-2-line me-1"></i>PDF</button></div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h5 class="mb-1">Vista previa del reporte</h5><small id="period" class="text-muted">Seleccioná los filtros y ejecutá la búsqueda</small>
    </div>
    <div class="card-body p-0">
        <div id="empty" class="text-center text-muted py-5"><i class="ri-file-chart-line d-block mb-2" style="font-size:3rem"></i>El reporte PDF se mostrará aquí.</div><iframe id="viewer" title="Ventas por Fechas y Cajas" class="w-100 border-0 d-none" style="min-height:75vh"></iframe>
    </div>
</div>
<script>
    const form = document.getElementById('filters'),
        viewer = document.getElementById('viewer'),
        empty = document.getElementById('empty');
    let pdfUrl = '';
    const cashBoxMulti = erpEnhanceMultiSelect(cashBoxes);

    function params() {
        return new URLSearchParams(new FormData(form))
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
        pdfUrl = `${window.APP_BASE_URL}/api/reports/sales/by-date-cash/pdf?${params()}`;
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
        cashBoxMulti.setAll(false);
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
        location.href = `${window.APP_BASE_URL}/api/reports/sales/by-date-cash/export?${p}`
    }));
    openPdf.addEventListener('click', () => {
        if (valid()) window.open(pdfUrl || `${window.APP_BASE_URL}/api/reports/sales/by-date-cash/pdf?${params()}`, '_blank', 'noopener')
    });
    fetch(`${window.APP_BASE_URL}/api/reports/sales/by-date-cash/options`, {
        headers: {
            Accept: 'application/json'
        }
    }).then(r => r.ok ? r.json() : Promise.reject()).then(d => {
        (d.cash_boxes || []).forEach(box => cashBoxes.add(new Option(`${box.name}${box.branch_name?' · '+box.branch_name:''}`, box.id)));
        cashBoxMulti.refresh()
    }).catch(() => erpAlert('No se pudieron cargar las cajas disponibles.'));
</script>@endsection