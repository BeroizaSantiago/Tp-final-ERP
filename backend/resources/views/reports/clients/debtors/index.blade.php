{{-- Vista: Listado de Deudores de cuenta corriente. --}}@extends('layouts.app')@section('content')<div class="mb-4">
    <h4>Listado de Deudores</h4>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form id="filters" class="row g-3 align-items-end">
            <div class="col-md-3"><x-multi-select id="clientIds" name="client_ids[]" label="Cliente" /></div>
            <div class="col-md-3"><x-multi-select id="sellers" name="sellers[]" label="Vendedor" /></div>
            <div class="col-md-3"><x-multi-select id="provinces" name="provinces[]" label="Provincia" /></div>
            <div class="col-md-3"><label class="form-label">Tipo de Deuda</label><select name="debt_type" class="form-select">
                    <option value="expired">Vencida</option>
                    <option value="future">Futura</option>
                    <option value="both" selected>Ambas</option>
                </select></div>
            <div class="col-12 d-flex justify-content-end gap-2"><button class="btn btn-primary">Buscar</button><button id="clear" type="button" class="btn btn-secondary">Limpiar</button><button type="button" data-export="xlsx" class="btn btn-success">Excel</button><button type="button" data-export="csv" class="btn btn-outline-success future-action">CSV</button><button type="button" id="openPdf" class="btn btn-danger">PDF</button></div>
        </form>
    </div>
</div><x-report-pdf-viewer title="Listado de Deudores" />
<script>
    const endpoint = `${window.APP_BASE_URL}/api/reports/clients/debtors`,
        form = filters,
        multis = [erpEnhanceMultiSelect(clientIds), erpEnhanceMultiSelect(sellers), erpEnhanceMultiSelect(provinces)];

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
        multis.forEach(m => m.setAll(false))
    };
    document.querySelectorAll('[data-export]').forEach(b => b.onclick = () => {
        const x = p();
        x.set('format', b.dataset.export);
        location.href = `${endpoint}/export?${x}`
    });
    openPdf.onclick = () => window.open(`${endpoint}/pdf?${p()}`, '_blank');
    fetch(`${endpoint}/options`).then(r => r.json()).then(d => {
        d.clients.forEach(v => clientIds.add(new Option(v.name, v.id)));
        d.sellers.forEach(v => sellers.add(new Option(v, v)));
        d.provinces.forEach(v => provinces.add(new Option(v, v)));
        multis.forEach(m => m.refresh())
    })
</script>@endsection