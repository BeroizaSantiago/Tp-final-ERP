{{-- Vista: Reporte de Ventas Consolidado. Permite filtrar, visualizar y exportar el reporte integral de ventas. --}}
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h4 class="mb-1">Reporte de Ventas Consolidado</h4>
        <small class="text-muted">Resumen de ventas por sucursal, categoría, marca, medio de pago y canal</small>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form id="reportFilters" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="dateFrom">Fecha Desde</label>
                <input class="form-control" id="dateFrom" name="date_from" type="date" value="{{ now()->startOfMonth()->toDateString() }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="dateTo">Fecha Hasta</label>
                <input class="form-control" id="dateTo" name="date_to" type="date" value="{{ now()->toDateString() }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="branch">Sucursal</label>
                <select class="form-select" id="branch" name="branch">
                    <option value="">Todas las sucursales</option>
                </select>
            </div>
            <div class="col-md-3 d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="submit"><i class="ri-search-line me-1"></i>Buscar</button>
                <button class="btn btn-secondary" id="clearFilters" type="button"><i class="ri-eraser-line me-1"></i>Limpiar</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h5 class="mb-1">Vista previa del reporte</h5>
            <small class="text-muted" id="reportPeriod">Seleccioná los filtros y ejecutá la búsqueda</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-success" type="button" data-export="xlsx"><i class="ri-file-excel-2-line me-1"></i>Excel</button>
            <button class="btn btn-outline-success future-action" type="button" data-export="csv"><i class="ri-file-text-line me-1"></i>CSV</button>
            <button class="btn btn-danger" type="button" id="openPdf"><i class="ri-file-pdf-2-line me-1"></i>PDF</button>
        </div>
    </div>
    <div class="card-body p-0">
        <div id="reportEmpty" class="text-center text-muted py-5">
            <i class="ri-file-chart-line d-block mb-2" style="font-size:3rem"></i>
            El reporte PDF se mostrará aquí.
        </div>
        <iframe id="reportViewer" title="Reporte de Ventas Consolidado" class="w-100 border-0 d-none" style="min-height:75vh"></iframe>
    </div>
</div>

<script>
const reportForm = document.getElementById('reportFilters');
const reportViewer = document.getElementById('reportViewer');
const reportEmpty = document.getElementById('reportEmpty');
let currentPdfUrl = '';

function reportParams() {
    const params = new URLSearchParams(new FormData(reportForm));
    if (!params.get('branch')) params.delete('branch');
    return params;
}

function validateDates() {
    if (!dateFrom.value || !dateTo.value) {
        erpAlert('Debés seleccionar ambas fechas.');
        return false;
    }
    if (dateFrom.value > dateTo.value) {
        erpAlert('La fecha hasta debe ser igual o posterior a la fecha desde.');
        return false;
    }
    return true;
}

function loadReport() {
    if (!validateDates()) return;
    currentPdfUrl = `${window.APP_BASE_URL}/api/reports/sales/consolidated/pdf?${reportParams().toString()}`;
    reportViewer.src = currentPdfUrl;
    reportViewer.classList.remove('d-none');
    reportEmpty.classList.add('d-none');
    reportPeriod.textContent = `${dateFrom.value} al ${dateTo.value}${branch.value ? ` · ${branch.value}` : ' · Todas las sucursales'}`;
}

reportForm.addEventListener('submit', event => {
    event.preventDefault();
    loadReport();
});

document.getElementById('clearFilters').addEventListener('click', () => {
    dateFrom.value = '{{ now()->startOfMonth()->toDateString() }}';
    dateTo.value = '{{ now()->toDateString() }}';
    branch.value = '';
    reportViewer.src = 'about:blank';
    reportViewer.classList.add('d-none');
    reportEmpty.classList.remove('d-none');
    reportPeriod.textContent = 'Seleccioná los filtros y ejecutá la búsqueda';
    currentPdfUrl = '';
});

document.querySelectorAll('[data-export]').forEach(button => button.addEventListener('click', () => {
    if (!validateDates()) return;
    const params = reportParams();
    params.set('format', button.dataset.export);
    window.location.href = `${window.APP_BASE_URL}/api/reports/sales/consolidated/export?${params.toString()}`;
}));

document.getElementById('openPdf').addEventListener('click', () => {
    if (!validateDates()) return;
    if (!currentPdfUrl) loadReport();
    window.open(currentPdfUrl || `${window.APP_BASE_URL}/api/reports/sales/consolidated/pdf?${reportParams().toString()}`, '_blank', 'noopener');
});

fetch(`${window.APP_BASE_URL}/api/reports/sales/consolidated/options`, { headers: { Accept: 'application/json' } })
    .then(response => response.ok ? response.json() : Promise.reject())
    .then(data => (data.branches ?? []).forEach(name => branch.add(new Option(name, name))))
    .catch(() => erpAlert('No se pudieron cargar las sucursales disponibles.'));
</script>
@endsection
