{{-- Vista: Listado de Comisiones. Consulta comisiones de vendedores y muestra el PDF generado. --}}
@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="mb-1">Listado de Comisiones</h4>
    <small class="text-muted">Comisiones por venta, cobranza o ganancia</small>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form id="commissionFilters" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label" for="dateFrom">Fecha Desde</label>
                <input class="form-control" id="dateFrom" name="date_from" type="date" value="{{ now()->startOfMonth()->toDateString() }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="dateTo">Fecha Hasta</label>
                <input class="form-control" id="dateTo" name="date_to" type="date" value="{{ now()->toDateString() }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="criterion">Criterio</label>
                <select class="form-select" id="criterion" name="criterion">
                    <option value="sale">Venta</option>
                    <option value="collection">Cobranza</option>
                    <option value="profit">Ganancia</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="seller">Vendedor</label>
                <select class="form-select" id="seller" name="seller_id">
                    <option value="">Todos</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="client">Cliente</label>
                <select class="form-select" id="client" name="client_id">
                    <option value="">Todos</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="pointOfSale">Punto de Venta</label>
                <select class="form-select" id="pointOfSale" name="point_of_sale">
                    <option value="">Todos</option>
                </select>
            </div>
            <div class="col-md-5">
                <div class="form-check mb-2">
                    <input class="form-check-input" id="grossSale" name="gross_sale_commission" type="checkbox" value="1">
                    <label class="form-check-label" for="grossSale">Comisión de venta en bruto</label>
                </div>
                <small class="text-muted">Solo afecta el criterio Venta. Desmarcado calcula sobre el neto fiscal.</small>
            </div>
            <div class="col-md-7 d-flex flex-wrap justify-content-md-end gap-2">
                <button class="btn btn-primary" type="submit"><i class="ri-search-line me-1"></i>Buscar</button>
                <button class="btn btn-secondary" id="clearFilters" type="button"><i class="ri-eraser-line me-1"></i>Limpiar</button>
                <button class="btn btn-success" type="button" data-export="xlsx"><i class="ri-file-excel-2-line me-1"></i>Excel</button>
                <button class="btn btn-outline-success future-action" type="button" data-export="csv"><i class="ri-file-text-line me-1"></i>CSV</button>
                <button class="btn btn-danger" type="button" id="openPdf"><i class="ri-file-pdf-2-line me-1"></i>PDF</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-1">Vista previa del reporte</h5>
        <small class="text-muted" id="reportPeriod">Seleccioná los filtros y ejecutá la búsqueda</small>
    </div>
    <div class="card-body p-0">
        <div id="reportEmpty" class="text-center text-muted py-5">
            <i class="ri-file-chart-line d-block mb-2" style="font-size:3rem"></i>
            El reporte PDF se mostrará aquí.
        </div>
        <iframe id="reportViewer" title="Listado de Comisiones" class="w-100 border-0 d-none" style="min-height:75vh"></iframe>
    </div>
</div>

<script>
    const commissionForm = document.getElementById('commissionFilters');
    const reportViewer = document.getElementById('reportViewer');
    const reportEmpty = document.getElementById('reportEmpty');
    let currentPdfUrl = '';

    function commissionParams() {
        const params = new URLSearchParams(new FormData(commissionForm));
        ['seller_id', 'client_id', 'point_of_sale'].forEach(name => {
            if (!params.get(name)) params.delete(name);
        });
        if (!grossSale.checked) params.set('gross_sale_commission', '0');
        return params;
    }

    function validPeriod() {
        if (!dateFrom.value || !dateTo.value || dateFrom.value > dateTo.value) {
            erpAlert('Revisá el rango de fechas seleccionado.');
            return false;
        }
        return true;
    }

    function loadReport() {
        if (!validPeriod()) return;
        currentPdfUrl = `${window.APP_BASE_URL}/api/reports/sales/commissions/pdf?${commissionParams()}`;
        reportViewer.src = currentPdfUrl;
        reportViewer.classList.remove('d-none');
        reportEmpty.classList.add('d-none');
        reportPeriod.textContent = `${dateFrom.value} al ${dateTo.value} · ${criterion.options[criterion.selectedIndex].text}`;
    }

    commissionForm.addEventListener('submit', event => {
        event.preventDefault();
        loadReport();
    });
    document.getElementById('clearFilters').addEventListener('click', () => {
        commissionForm.reset();
        dateFrom.value = '{{ now()->startOfMonth()->toDateString() }}';
        dateTo.value = '{{ now()->toDateString() }}';
        reportViewer.src = 'about:blank';
        reportViewer.classList.add('d-none');
        reportEmpty.classList.remove('d-none');
        reportPeriod.textContent = 'Seleccioná los filtros y ejecutá la búsqueda';
        currentPdfUrl = '';
    });
    document.querySelectorAll('[data-export]').forEach(button => button.addEventListener('click', () => {
        if (!validPeriod()) return;
        const params = commissionParams();
        params.set('format', button.dataset.export);
        window.location.href = `${window.APP_BASE_URL}/api/reports/sales/commissions/export?${params}`;
    }));
    document.getElementById('openPdf').addEventListener('click', () => {
        if (!validPeriod()) return;
        window.open(currentPdfUrl || `${window.APP_BASE_URL}/api/reports/sales/commissions/pdf?${commissionParams()}`, '_blank', 'noopener');
    });
    criterion.addEventListener('change', () => {
        grossSale.disabled = criterion.value !== 'sale';
        if (grossSale.disabled) grossSale.checked = false;
    });

    fetch(`${window.APP_BASE_URL}/api/reports/sales/commissions/options`, {
            headers: {
                Accept: 'application/json'
            }
        })
        .then(response => response.ok ? response.json() : Promise.reject())
        .then(data => {
            (data.sellers ?? []).forEach(row => seller.add(new Option(row.name || row.username, row.id)));
            (data.clients ?? []).forEach(row => client.add(new Option(`${row.name}${row.document_number ? ` · ${row.document_number}` : ''}`, row.id)));
            (data.points_of_sale ?? []).forEach(name => pointOfSale.add(new Option(name, name)));
        })
        .catch(() => erpAlert('No se pudieron cargar las opciones del reporte.'));
</script>
@endsection