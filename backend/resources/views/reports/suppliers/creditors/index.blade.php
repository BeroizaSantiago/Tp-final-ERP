{{-- Vista: listado de saldos acreedores de proveedores. --}}
@extends('layouts.app')
@section('content')
<div class="mb-4"><h4>Listado de Acreedores</h4></div>
<div class="card mb-4"><div class="card-body">
    <form id="filters" class="row g-3 align-items-end">
        <div class="col-md-5"><x-multi-select id="providerIds" name="provider_ids[]" label="Proveedor" /></div>
        <div class="col-md-3"><label class="form-label">Tipo de Deuda</label><select name="debt_type" class="form-select"><option value="expired">Deuda Vencida</option><option value="future">Deuda Futura</option><option value="both" selected>Ambas</option></select></div>
        <div class="col-md-4 d-flex flex-wrap justify-content-end gap-2">
            <button class="btn btn-primary">Buscar</button><button id="clear" type="button" class="btn btn-secondary">Limpiar</button>
            <button data-export="xlsx" type="button" class="btn btn-success">Excel</button><button data-export="csv" type="button" class="btn btn-outline-success future-action">CSV</button><button id="openPdf" type="button" class="btn btn-danger">PDF</button>
        </div>
    </form>
</div></div>
<x-report-pdf-viewer title="Listado de Acreedores" />
<script>
const endpoint = `${window.APP_BASE_URL}/api/reports/suppliers/creditors`, form = filters, multi = erpEnhanceMultiSelect(providerIds);
function params() { return new URLSearchParams(new FormData(form)); }
function load() { reportViewer.src = `${endpoint}/pdf?${params()}`; reportViewer.classList.remove('d-none'); reportEmpty.classList.add('d-none'); }
form.onsubmit = event => { event.preventDefault(); load(); };
clear.onclick = () => { form.reset(); multi.setAll(false); };
document.querySelectorAll('[data-export]').forEach(button => button.onclick = () => { const query = params(); query.set('format', button.dataset.export); location.href = `${endpoint}/export?${query}`; });
openPdf.onclick = () => window.open(`${endpoint}/pdf?${params()}`, '_blank');
fetch(`${endpoint}/options`).then(response => response.json()).then(data => { data.providers.forEach(provider => providerIds.add(new Option(provider.name, provider.id))); multi.refresh(); });
</script>
@endsection
