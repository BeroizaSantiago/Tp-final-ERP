{{-- Vista compartida: Resultado Económico y Resultado Financiero de la empresa. --}}
@extends('layouts.app')
@section('content')
@php($isEconomic=$type==='economic')
<div class="mb-4">
    <h4>{{ $isEconomic?'Resultado Económico':'Resultado Financiero' }}</h4>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form id="filters" class="row g-3 align-items-end">
            <div class="col-md-3"><label class="form-label">Fecha Desde</label><input id="dateFrom" name="date_from" type="date" class="form-control" value="{{ now()->subMonth()->toDateString() }}"></div>
            <div class="col-md-3"><label class="form-label">Fecha Hasta</label><input id="dateTo" name="date_to" type="date" class="form-control" value="{{ now()->toDateString() }}"></div>
            <div class="col-md-3"><label class="form-label">Sucursal</label><select id="branch" name="branch" class="form-select">
                    <option value="">Todas las sucursales</option>
                </select></div>
            <div class="col-md-3 d-flex flex-wrap justify-content-end gap-2"><button class="btn btn-primary">Buscar</button><button id="clear" type="button" class="btn btn-secondary">Limpiar</button><button data-export="xlsx" type="button" class="btn btn-success">Excel</button><button data-export="csv" type="button" class="btn btn-outline-success future-action">CSV</button><button id="openPdf" type="button" class="btn btn-danger">PDF</button></div>
        </form>
    </div>
</div>
<x-report-pdf-viewer title="{{ $isEconomic?'Resultado Económico':'Resultado Financiero' }}" />
<script>
    const endpoint = `${window.APP_BASE_URL}/api/reports/company-position/{{ $type }}`,
        form = filters;

    function params() {
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
        reportViewer.src = `${endpoint}/pdf?${params()}`;
        reportViewer.classList.remove('d-none');
        reportEmpty.classList.add('d-none')
    }
    form.onsubmit = e => {
        e.preventDefault();
        load()
    };
    clear.onclick = () => form.reset();
    document.querySelectorAll('[data-export]').forEach(b => b.onclick = () => {
        if (valid()) {
            const q = params();
            q.set('format', b.dataset.export);
            location.href = `${endpoint}/export?${q}`
        }
    });
    openPdf.onclick = () => {
        if (valid()) window.open(`${endpoint}/pdf?${params()}`, '_blank')
    };
    fetch(`${window.APP_BASE_URL}/api/reports/company-position/options`).then(r => r.json()).then(d => d.branches.forEach(v => branch.add(new Option(v, v))));
</script>
@endsection