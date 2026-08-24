{{-- Vista: Listado general de Compras, una fila por comprobante. --}}@extends('layouts.app')@section('content')
<div class="mb-4">
    <h4>Listado de Compras</h4><small class="text-muted">Comprobantes de proveedores</small>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form id="filters" class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label">Fecha Desde</label><input id="dateFrom" name="date_from" type="date" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}"></div>
            <div class="col-md-2"><label class="form-label">Fecha Hasta</label><input id="dateTo" name="date_to" type="date" class="form-control" value="{{ now()->toDateString() }}"></div>
            <div class="col-md-2"><x-multi-select id="branches" name="branches[]" label="Sucursal" /></div>
            <div class="col-md-2"><x-multi-select id="operationTypes" name="operation_types[]" label="Tipo de Operación" /></div>
            <div class="col-md-2"><x-multi-select id="providerIds" name="provider_ids[]" label="Proveedor" /></div>
            <div class="col-md-2"><x-multi-select id="provinces" name="provinces[]" label="Provincia" /></div>
            <div class="col-12 d-flex justify-content-end gap-2"><button class="btn btn-primary">Buscar</button><button id="clear" type="button" class="btn btn-secondary">Limpiar</button><button type="button" class="btn btn-success" data-export="xlsx">Excel</button><button type="button" class="btn btn-outline-success future-action" data-export="csv">CSV</button><button type="button" id="openPdf" class="btn btn-danger">PDF</button></div>
        </form>
    </div>
</div><x-report-pdf-viewer title="Listado de Compras" />
<script>
    const endpoint = `${window.APP_BASE_URL}/api/reports/purchases/list`,
        form = filters,
        multis = [erpEnhanceMultiSelect(branches), erpEnhanceMultiSelect(operationTypes), erpEnhanceMultiSelect(providerIds), erpEnhanceMultiSelect(provinces)];

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
    form.addEventListener('submit', e => {
        e.preventDefault();
        load()
    });
    clear.onclick = () => {
        form.reset();
        multis.forEach(m => m.setAll(false));
        reportViewer.src = 'about:blank';
        reportViewer.classList.add('d-none');
        reportEmpty.classList.remove('d-none')
    };
    document.querySelectorAll('[data-export]').forEach(b => b.onclick = () => {
        if (valid()) {
            const x = p();
            x.set('format', b.dataset.export);
            location.href = `${endpoint}/export?${x}`
        }
    });
    openPdf.onclick = () => {
        if (valid()) window.open(`${endpoint}/pdf?${p()}`, '_blank')
    };
    fetch(`${endpoint}/options`).then(r => r.json()).then(d => {
        d.branches.forEach(v => branches.add(new Option(v, v)));
        d.types.forEach(v => operationTypes.add(new Option(v.label, v.value)));
        d.providers.forEach(v => providerIds.add(new Option(v.name, v.id)));
        d.provinces.forEach(v => provinces.add(new Option(v, v)));
        multis.forEach(m => m.refresh())
    })
</script>@endsection