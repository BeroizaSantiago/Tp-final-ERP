{{-- Vista: Listado general de Clientes. --}}@extends('layouts.app')@section('content')<div class="mb-4">
    <h4>Listado de Clientes</h4>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form id="filters" class="row g-3 align-items-end">
            <div class="col-md-5"><x-multi-select id="sellers" name="sellers[]" label="Vendedor" /></div>
            <div class="col-md-7 d-flex justify-content-end gap-2"><button class="btn btn-primary">Buscar</button><button id="clear" type="button" class="btn btn-secondary">Limpiar</button><button type="button" data-export="xlsx" class="btn btn-success">Excel</button><button type="button" data-export="csv" class="btn btn-outline-success future-action">CSV</button><button type="button" id="openPdf" class="btn btn-danger">PDF</button></div>
        </form>
    </div>
</div><x-report-pdf-viewer title="Listado de Clientes" />
<script>
    const endpoint = `${window.APP_BASE_URL}/api/reports/clients/list`,
        form = filters,
        multi = erpEnhanceMultiSelect(sellers);

    function p() {
        return new URLSearchParams(new FormData(form))
    }

    function load() {
        reportViewer.src = `${endpoint}/pdf?${p()}`;
        reportViewer.classList.remove('d-none');
        reportEmpty.classList.add('d-none')
    }
    form.onsubmit = e => {
        e.preventDefault();
        load()
    };
    clear.onclick = () => {
        form.reset();
        multi.setAll(false)
    };
    document.querySelectorAll('[data-export]').forEach(b => b.onclick = () => {
        const x = p();
        x.set('format', b.dataset.export);
        location.href = `${endpoint}/export?${x}`
    });
    openPdf.onclick = () => window.open(`${endpoint}/pdf?${p()}`, '_blank');
    fetch(`${endpoint}/options`).then(r => r.json()).then(d => {
        d.sellers.forEach(v => sellers.add(new Option(v, v)));
        multi.refresh()
    })
</script>@endsection