{{-- Vista: filtros y PDF del historial de movimientos de stock. --}}@extends('layouts.app')@section('content')<h4 class="mb-4">Movimientos de Stock</h4>
<div class="card mb-4">
    <div class="card-body">
        <form id="filters" class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label">Fecha Desde</label><input id="dateFrom" name="date_from" type="date" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}" required></div>
            <div class="col-md-2"><label class="form-label">Fecha Hasta</label><input id="dateTo" name="date_to" type="date" class="form-control" value="{{ now()->toDateString() }}" required></div>
            <div class="col-md-4"><x-multi-select id="products" name="product_ids[]" label="Producto" remote-url="{{ url('/api/products') }}" /></div>
            <div class="col-md-4"><label class="form-label">Nombre</label><input name="name" class="form-control" placeholder="Buscar por nombre..."></div>
            <div class="col-md-3"><x-multi-select id="branches" name="branches[]" label="Sucursal" /></div>
            <div class="col-md-3"><x-multi-select id="warehouses" name="warehouses[]" label="Depósito" /></div>
            <div class="col-md-2"><x-multi-select id="modules" name="modules[]" label="Módulo" /></div>
            <div class="col-md-2"><x-multi-select id="operationTypes" name="operation_types[]" label="Tipo de Operación" /></div>
            <div class="col-md-2"><x-multi-select id="reasons" name="reason_ids[]" label="Motivo" /></div>
            <div class="col-12 d-flex flex-wrap justify-content-end gap-2"><button class="btn btn-primary">Buscar</button><button id="clear" type="button" class="btn btn-secondary">Limpiar</button><button data-export="xlsx" type="button" class="btn btn-success">Excel</button><button data-export="csv" type="button" class="btn btn-outline-success future-action">CSV</button><button id="openPdf" type="button" class="btn btn-danger">PDF</button></div>
        </form>
    </div>
</div><x-report-pdf-viewer title="Movimientos de Stock" />
<script>
    const endpoint = `${window.APP_BASE_URL}/api/reports/stock/movements`,
        form = filters,
        enhancers = [products, branches, warehouses, modules, operationTypes, reasons].map(erpEnhanceMultiSelect);
    let allTypes = [];

    function p() {
        return new URLSearchParams(new FormData(form))
    }

    function valid() {
        if (!dateFrom.value || !dateTo.value || dateFrom.value > dateTo.value) {
            erpAlert('Revisá las fechas.');
            return false
        }
        return true
    }

    function load() {
        if (!valid()) return;
        reportViewer.src = `${endpoint}/pdf?${p()}`;
        reportViewer.classList.remove('d-none');
        reportEmpty.classList.add('d-none')
    }

    function refreshTypes() {
        const selected = [...modules.selectedOptions].map(o => o.value);
        operationTypes.innerHTML = '';
        allTypes.filter(t => !selected.length || selected.includes(t.module)).forEach(t => operationTypes.add(new Option(t.label, t.value)));
        enhancers[4].refresh()
    }
    form.onsubmit = e => {
        e.preventDefault();
        load()
    };
    clear.onclick = () => {
        form.reset();
        enhancers.forEach(x => x.setAll(false));
        refreshTypes()
    };
    modules.onchange = refreshTypes;
    document.querySelectorAll('[data-export]').forEach(b => b.onclick = () => {
        if (valid()) {
            const q = p();
            q.set('format', b.dataset.export);
            location.href = `${endpoint}/export?${q}`
        }
    });
    openPdf.onclick = () => {
        if (valid()) window.open(`${endpoint}/pdf?${p()}`, '_blank')
    };
    fetch(`${endpoint}/options`).then(r => r.json()).then(d => {
        d.products.forEach(v => products.add(new Option(`${v.code??''} · ${v.name}`, v.id)));
        d.branches.forEach(v => branches.add(new Option(v, v)));
        d.warehouses.forEach(v => warehouses.add(new Option(v, v)));
        Object.entries(d.modules).forEach(([k, v]) => modules.add(new Option(v, k)));
        d.reasons.forEach(v => reasons.add(new Option(v.name, v.id)));
        allTypes = d.operation_types;
        refreshTypes();
        enhancers.forEach(x => x.refresh())
    });
</script>@endsection
