{{-- Vista: Listado de Gastos Varios agrupado por tipo. --}}@extends('layouts.app')@section('content')
<div class="mb-4">
    <h4>Listado de Gastos Varios</h4><small class="text-muted">Gastos agrupados por categoría y subcategoría</small>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form id="filters" class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label">Fecha Desde</label><input id="dateFrom" name="date_from" type="date" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}"></div>
            <div class="col-md-2"><label class="form-label">Fecha Hasta</label><input id="dateTo" name="date_to" type="date" class="form-control" value="{{ now()->toDateString() }}"></div>
            <div class="col-md-3"><x-multi-select id="branches" name="branches[]" label="Sucursal" /></div>
            <div class="col-md-3"><x-multi-select id="expenseTypeIds" name="expense_type_ids[]" label="Tipo de Gasto" /></div>
            <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary">Buscar</button><button id="clear" type="button" class="btn btn-secondary">Limpiar</button></div>
            <div class="col-12 d-flex justify-content-end gap-2"><button type="button" class="btn btn-success" data-export="xlsx">Excel</button><button type="button" class="btn btn-outline-success future-action" data-export="csv">CSV</button><button type="button" id="openPdf" class="btn btn-danger">PDF</button></div>
        </form>
    </div>
</div><x-report-pdf-viewer title="Listado de Gastos Varios" />
<script>
    const endpoint = `${window.APP_BASE_URL}/api/reports/purchases/misc-expenses`,
        form = filters,
        branchMulti = erpEnhanceMultiSelect(branches),
        typeMulti = erpEnhanceMultiSelect(expenseTypeIds);

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
        branchMulti.setAll(false);
        typeMulti.setAll(false);
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
        d.expense_types.forEach(v => expenseTypeIds.add(new Option(v.name, v.id)));
        branchMulti.refresh();
        typeMulti.refresh()
    })
</script>@endsection