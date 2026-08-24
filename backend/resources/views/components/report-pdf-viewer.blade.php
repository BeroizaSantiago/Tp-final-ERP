@props(['title']){{-- Componente: visor PDF embebido reutilizable para reportes. --}}
<div class="card">
    <div class="card-header">
        <h5 class="mb-1">Vista previa</h5><small class="text-muted">{{ $title }}</small>
    </div>
    <div class="card-body p-0">
        <div id="reportEmpty" class="text-center text-muted py-5">El reporte PDF se mostrará aquí.</div><iframe id="reportViewer" title="{{ $title }}" class="w-100 border-0 d-none" style="min-height:75vh"></iframe>
    </div>
</div>