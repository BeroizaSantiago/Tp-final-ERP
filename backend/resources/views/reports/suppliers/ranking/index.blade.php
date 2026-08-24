{{-- Vista: ranking de proveedores por compras netas del período. --}}
@extends('layouts.app')
@section('content')
<div class="mb-4"><h4>Ranking de Proveedores</h4></div>
<div class="card mb-4"><div class="card-body">
    <form id="filters" class="row g-3 align-items-end">
        <div class="col-md-3"><label class="form-label">Fecha Desde</label><input id="dateFrom" name="date_from" type="date" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}"></div>
        <div class="col-md-3"><label class="form-label">Fecha Hasta</label><input id="dateTo" name="date_to" type="date" class="form-control" value="{{ now()->toDateString() }}"></div>
        <div class="col-md-2"><label class="form-label">Top</label><input name="top" type="number" min="1" max="500" value="10" list="tops" class="form-control"><datalist id="tops"><option value="5"><option value="10"><option value="20"><option value="50"><option value="100"></datalist></div>
        <div class="col-md-4 d-flex flex-wrap justify-content-end gap-2">
            <button class="btn btn-primary">Buscar</button><button id="clear" type="button" class="btn btn-secondary">Limpiar</button>
            <button data-export="xlsx" type="button" class="btn btn-success">Excel</button><button data-export="csv" type="button" class="btn btn-outline-success future-action">CSV</button><button id="openPdf" type="button" class="btn btn-danger">PDF</button>
        </div>
    </form>
</div></div>
<x-report-pdf-viewer title="Ranking de Proveedores" />
<script>
const endpoint = `${window.APP_BASE_URL}/api/reports/suppliers/ranking`, form = filters;
function params() { return new URLSearchParams(new FormData(form)); }
function valid() { if (!dateFrom.value || !dateTo.value || dateFrom.value > dateTo.value) { erpAlert('Revisá las fechas.'); return false; } return true; }
function load() { if (!valid()) return; reportViewer.src = `${endpoint}/pdf?${params()}`; reportViewer.classList.remove('d-none'); reportEmpty.classList.add('d-none'); }
form.onsubmit = event => { event.preventDefault(); load(); };
clear.onclick = () => form.reset();
document.querySelectorAll('[data-export]').forEach(button => button.onclick = () => { if (valid()) { const query = params(); query.set('format', button.dataset.export); location.href = `${endpoint}/export?${query}`; } });
openPdf.onclick = () => { if (valid()) window.open(`${endpoint}/pdf?${params()}`, '_blank'); };
</script>
@endsection
