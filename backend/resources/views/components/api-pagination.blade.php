{{-- Componente: Paginación de listados. Renderiza los controles reutilizables de paginación. --}}
@php
    $rootId = $paginationId ?? null;
    $infoId = $rootId ? $rootId.'Info' : 'paginationInfo';
    $controlsId = $rootId ? $rootId.'Controls' : 'pagination';
@endphp
<div @if($rootId) id="{{ $rootId }}" @endif class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2"
     data-api-pagination
     data-auto-pagination="true">
    <small id="{{ $infoId }}" class="text-muted" data-pagination-info></small>
    <div id="{{ $controlsId }}" class="d-flex gap-1" data-pagination-controls></div>
</div>
