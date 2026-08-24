{{-- Vista: listado general del maestro de proveedores. --}}
@extends('layouts.app')
@section('content')
<div class="mb-4">
    <h4>Listado de Proveedores</h4>
</div>
<div class="d-flex justify-content-end gap-2 mb-3">
    <button data-export="xlsx" class="btn btn-success">Excel</button>
    <button data-export="csv" class="btn btn-outline-success future-action">CSV</button>
    <button id="openPdf" class="btn btn-danger">PDF</button>
</div>
<x-report-pdf-viewer title="Listado de Proveedores" />
<script>
    const endpoint = `${window.APP_BASE_URL}/api/reports/suppliers/list`;
    reportViewer.src = endpoint + '/pdf';
    reportViewer.classList.remove('d-none');
    reportEmpty.classList.add('d-none');
    document.querySelectorAll('[data-export]').forEach(button => button.onclick = () => {
        location.href = `${endpoint}/export?format=${button.dataset.export}`;
    });
    openPdf.onclick = () => window.open(endpoint + '/pdf', '_blank');
</script>
@endsection