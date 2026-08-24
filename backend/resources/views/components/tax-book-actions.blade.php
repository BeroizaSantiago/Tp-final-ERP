{{-- Componente: Acciones de libros impositivos. Renderiza las acciones compartidas de exportación fiscal. --}}
@props(['exportEndpoint'])
<div class="d-flex flex-wrap gap-2">
    <button type="button" class="btn btn-outline-primary future-action tax-future-action" data-name="Libro IVA Digital"><i class="ri-file-text-line me-1"></i>Libro IVA Digital</button>
    <button type="button" class="btn btn-outline-primary future-action tax-future-action" data-name="Generar CITI"><i class="ri-file-list-3-line me-1"></i>Generar CITI</button>
    <div class="btn-group"><button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"><i class="ri-download-2-line me-1"></i>Exportar</button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><button class="dropdown-item export-action" data-format="xlsx">Excel (.xlsx)</button></li>
            <li><button class="dropdown-item export-action" data-format="csv">CSV (.csv)</button></li>
        </ul>
    </div>
</div>
<script>
    window.taxBookExportEndpoint = @json($exportEndpoint);
</script>
