{{-- Vista: Ranking de Clientes. --}}@extends('layouts.app')@section('content')<div class="mb-4">
    <h4>Ranking de Clientes</h4>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form id="filters" class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label">Fecha Desde</label><input id="dateFrom" name="date_from" type="date" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}"></div>
            <div class="col-md-2"><label class="form-label">Fecha Hasta</label><input id="dateTo" name="date_to" type="date" class="form-control" value="{{ now()->toDateString() }}"></div>
            <div class="col-md-2"><label class="form-label">Top</label><input name="top" type="number" min="1" max="500" value="10" list="tops" class="form-control"><datalist id="tops">
                    <option value="5">
                    <option value="10">
                    <option value="20">
                    <option value="50">
                    <option value="100">
                </datalist></div>
            <div class="col-md-2"><label class="form-label">Ordenar por</label><select name="order_by" class="form-select">
                    <option value="quantity">Cantidad</option>
                    <option value="total">Total</option>
                </select></div>
            <div class="col-md-4 d-flex justify-content-end gap-2"><button class="btn btn-primary">Buscar</button><button id="clear" type="button" class="btn btn-secondary">Limpiar</button><button type="button" data-export="xlsx" class="btn btn-success">Excel</button><button type="button" data-export="csv" class="btn btn-outline-success future-action">CSV</button><button type="button" id="openPdf" class="btn btn-danger">PDF</button></div>
        </form>
    </div>
</div><x-report-pdf-viewer title="Ranking de Clientes" />
<script>
    const endpoint = `${window.APP_BASE_URL}/api/reports/clients/ranking`,
        form = filters;

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
    form.onsubmit = e => {
        e.preventDefault();
        load()
    };
    clear.onclick = () => form.reset();
    document.querySelectorAll('[data-export]').forEach(b => b.onclick = () => {
        if (valid()) {
            const x = p();
            x.set('format', b.dataset.export);
            location.href = `${endpoint}/export?${x}`
        }
    });
    openPdf.onclick = () => {
        if (valid()) window.open(`${endpoint}/pdf?${p()}`, '_blank')
    }
</script>@endsection