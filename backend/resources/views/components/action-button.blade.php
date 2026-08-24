{{-- Componente: Botón de acción. Renderiza botones reutilizables con estilos semánticos uniformes. --}}
@props([
    'action',
    'href' => null,
    'label' => null,
    'icon' => null,
    'buttonType' => 'button',
    'iconOnly' => false,
])
@php
    $definitions = [
        'edit' => ['Editar', 'ri-edit-line', 'btn-sm btn-warning'],
        'view' => ['Ver', 'ri-eye-line', 'btn-sm btn-primary'],
        'save' => ['Guardar', 'ri-save-line', 'btn-success'],
        'delete' => ['Eliminar', 'ri-delete-bin-line', 'btn-sm btn-danger'],
        'disable' => ['Desactivar', 'ri-forbid-line', 'btn-sm btn-danger'],
        'back' => ['Volver', 'ri-arrow-left-line', 'btn-secondary'],
        'new' => ['Nuevo', 'ri-add-line', 'btn-primary'],
    ];
    [$defaultLabel, $defaultIcon, $classes] = $definitions[$action] ?? ['', '', 'btn-secondary'];
    $text = $label ?? $defaultLabel;
    $actionIcon = $icon ?? $defaultIcon;
    if ($iconOnly) {
        $classes = collect(explode(' ', $classes))->reject(fn ($class) => $class === 'btn-sm')->implode(' ');
    }
@endphp

@php($iconOnlyClasses = $iconOnly ? 'rounded-pill btn-icon' : '')

@if($href)
    <a href="{{ $href }}" {{ $attributes->class("btn {$classes} {$iconOnlyClasses} erp-action erp-action-{$action}")->merge(['data-erp-action' => $action, 'data-erp-action-ignore' => $iconOnly ? 'true' : null, 'title' => $iconOnly ? $text : null, 'aria-label' => $text]) }}>
        @if($actionIcon)<i class="icon-base ri {{ $actionIcon }}{{ $iconOnly ? '' : ' me-1' }}"></i>@endif<span @class(['visually-hidden' => $iconOnly])>{{ $text }}</span>
    </a>
@else
    <button type="{{ $buttonType }}" {{ $attributes->class("btn {$classes} {$iconOnlyClasses} erp-action erp-action-{$action}")->merge(['data-erp-action' => $action, 'data-erp-action-ignore' => $iconOnly ? 'true' : null, 'title' => $iconOnly ? $text : null, 'aria-label' => $text]) }}>
        @if($actionIcon)<i class="icon-base ri {{ $actionIcon }}{{ $iconOnly ? '' : ' me-1' }}"></i>@endif<span @class(['visually-hidden' => $iconOnly])>{{ $text }}</span>
    </button>
@endif
