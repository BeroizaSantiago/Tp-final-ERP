{{-- Componente: Contenido de la barra superior. Muestra búsqueda, accesos rápidos, tema y usuario activo. --}}
@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

$erpShortcuts = [
    ['key' => 'F7', 'title' => 'Consultar precios', 'description' => 'Precios y existencias', 'icon' => 'ri-search-eye-line', 'action' => 'priceLookup'],
    ['key' => 'F2', 'title' => 'Nueva venta', 'description' => 'Crear factura', 'icon' => 'ri-shopping-cart-2-line', 'url' => url('/demo/sales/create')],
    ['key' => 'F3', 'title' => 'Caja', 'description' => 'Planillas de caja', 'icon' => 'ri-cash-line', 'url' => url('/demo/finance/cash-sheets')],
    ['key' => 'F6', 'title' => 'Stock', 'description' => 'Consultar inventario', 'icon' => 'ri-archive-stack-line', 'url' => url('/demo/stock/inventory')],
    ['key' => 'F4', 'title' => 'Compras', 'description' => 'Gestión de compras', 'icon' => 'ri-shopping-bag-3-line', 'url' => url('/demo/purchases')]
];

$erpSearchItems = [];
$collectSearchItems = function ($items, array $parents = [], ?string $parentIcon = null) use (&$collectSearchItems, &$erpSearchItems) {
    foreach ($items as $item) {
        if (isset($item->menuHeader)) continue;

        $name = isset($item->name) ? __($item->name) : null;
        $icon = $item->icon ?? $parentIcon ?? 'ri-arrow-right-s-line';

        if (isset($item->url) && $name) {
            $erpSearchItems[] = [
                'title' => $name,
                'section' => implode(' / ', $parents),
                'url' => url($item->url),
                'icon' => $icon,
            ];
        }

        if (isset($item->submenu)) {
            $collectSearchItems($item->submenu, [...$parents, $name], $icon);
        }
    }
};
$collectSearchItems($menuData[0]->menu);
@endphp

@if(isset($navbarFull))
<div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-6">
    <a href="{{url('/')}}" class="app-brand-link gap-2">
        <span class="app-brand-logo demo">@include('_partials.macros')</span>
        <span class="app-brand-text demo menu-text fw-bold">{{config('variables.templateName')}}</span>
    </a>
</div>
@endif

@if(!isset($navbarHideToggle))
<div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 {{ isset($contentNavbar) ? ' d-xl-none ' : '' }}">
    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
        <i class="icon-base ri ri-menu-line icon-md"></i>
    </a>
</div>
@endif

<div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

    <div class="navbar-nav align-items-center">
        <a class="nav-item nav-link erp-navbar-search-toggle d-flex align-items-center gap-2 px-0"
            href="javascript:void(0);" data-erp-search-open aria-label="Abrir buscador">
            <i class="icon-base ri ri-search-line icon-lg lh-0"></i>
            <span class="d-none d-md-inline text-muted">Buscar</span>
            <kbd class="d-none d-lg-inline erp-search-key">Ctrl K</kbd>
        </a>
    </div>

    <ul class="navbar-nav flex-row align-items-center ms-auto">

        <li class="nav-item navbar-dropdown dropdown-shortcuts dropdown ms-2">
            <a class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow"
                href="javascript:void(0);" data-bs-toggle="dropdown"
                aria-expanded="false" aria-label="Abrir atajos" title="Atajos de teclado">
                <i class="icon-base ri ri-star-line icon-md erp-shortcuts-toggle-icon"></i>
            </a>

            <div class="dropdown-menu dropdown-menu-end p-0 erp-shortcuts-menu">
                <div class="border-bottom px-4 py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0">Atajos</h6>
                        <small class="text-muted">Accesos rápidos del ERP</small>
                    </div>
                    <i class="icon-base ri ri-keyboard-line icon-md text-primary"></i>
                </div>

                <div class="row g-0 dropdown-shortcuts-list">
                    @foreach($erpShortcuts as $shortcut)
                        <div class="{{ $loop->last && $loop->count % 2 !== 0 ? 'col-12' : 'col-6' }} border-bottom {{ !$loop->last && $loop->iteration % 2 !== 0 ? 'border-end' : '' }}">
                            <div class="dropdown-shortcuts-item h-100 position-relative">
                                @if(isset($shortcut['action']))
                                    <button type="button" class="erp-shortcut-action stretched-link"
                                        onclick="openGlobalPriceLookup()" aria-label="{{ $shortcut['title'] }}"></button>
                                @else
                                    <a href="{{ $shortcut['url'] }}" class="stretched-link" aria-label="{{ $shortcut['title'] }}"></a>
                                @endif

                                <span class="dropdown-shortcuts-icon rounded-circle mb-2">
                                    <i class="icon-base ri {{ $shortcut['icon'] }} icon-md"></i>
                                </span>
                                <span class="d-block fw-medium text-heading">{{ $shortcut['title'] }}</span>
                                <small class="text-muted d-block">{{ $shortcut['description'] }}</small>
                                <kbd class="erp-shortcut-key mt-2">{{ $shortcut['key'] }}</kbd>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </li>

        <li class="nav-item ms-2 d-flex align-items-center">
            <button class="nav-link theme-toggle" type="button" data-theme-toggle
                aria-label="Activar modo oscuro" title="Activar modo oscuro">
                <svg class="theme-toggle-icon theme-toggle-moon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"></path>
                </svg>
                <svg class="theme-toggle-icon theme-toggle-sun" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="4"></circle>
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"></path>
                </svg>
            </button>
        </li>

        <li class="nav-item dropdown ms-2">
            <a class="nav-link dropdown-toggle hide-arrow d-flex align-items-center gap-2" href="javascript:void(0);" data-bs-toggle="dropdown">
                <i class="ri-user-3-line icon-md"></i>
                <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-end">
                <div class="dropdown-item-text">
                    <div class="fw-semibold">{{ Auth::user()->name }}</div>
                    <small class="text-muted">{{ Auth::user()->email }}</small>
                </div>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                    <i class="ri-settings-3-line me-2"></i>Mi perfil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="dropdown-item" type="submit">
                        <i class="ri-logout-box-r-line me-2"></i>Cerrar sesión
                    </button>
                </form>
            </div>
        </li>

    </ul>
</div>

<div class="erp-navbar-search d-none" data-erp-navbar-search>
    <div class="erp-navbar-search-bar">
        <i class="icon-base ri ri-search-line icon-lg text-muted" aria-hidden="true"></i>
        <input type="search" class="form-control border-0 shadow-none" data-erp-search-input
            placeholder="Buscar una pantalla..." autocomplete="off" aria-label="Buscar una pantalla">
        <button type="button" class="btn btn-text-secondary btn-icon rounded-pill" data-erp-search-close
            aria-label="Cerrar buscador" title="Cerrar">
            <i class="icon-base ri ri-close-line icon-lg"></i>
        </button>
    </div>
    <div class="erp-navbar-search-results d-none" data-erp-search-results></div>
</div>

<script>
const erpNavbarSearchItems = @json($erpSearchItems);

document.addEventListener('DOMContentLoaded', function() {
    const panel = document.querySelector('[data-erp-navbar-search]');
    const input = panel?.querySelector('[data-erp-search-input]');
    const results = panel?.querySelector('[data-erp-search-results]');
    const opener = document.querySelector('[data-erp-search-open]');
    const closer = panel?.querySelector('[data-erp-search-close]');
    if (!panel || !input || !results) return;

    const normalize = value => String(value ?? '').normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const close = () => {
        panel.classList.add('d-none');
        results.classList.add('d-none');
        input.value = '';
        results.innerHTML = '';
        opener?.focus();
    };
    const open = () => {
        panel.classList.remove('d-none');
        requestAnimationFrame(() => input.focus());
    };
    const render = () => {
        const query = normalize(input.value.trim());
        const matches = query ? erpNavbarSearchItems.filter(item =>
            normalize(`${item.title} ${item.section}`).includes(query)
        ).slice(0, 10) : [];

        if (!query) {
            results.innerHTML = '<div class="erp-navbar-search-empty"><i class="ri-search-eye-line"></i><span>Escribí para buscar módulos y pantallas</span></div>';
        } else if (!matches.length) {
            results.innerHTML = '<div class="erp-navbar-search-empty"><i class="ri-file-search-line"></i><span>No se encontraron pantallas</span></div>';
        } else {
            results.innerHTML = matches.map((item, index) => `
                <a class="erp-navbar-search-result" href="${item.url}" data-search-result="${index}">
                    <span class="erp-navbar-search-result-icon"><i class="${item.icon}"></i></span>
                    <span><strong>${item.title}</strong><small>${item.section || 'Inicio'}</small></span>
                    <i class="ri-arrow-right-s-line ms-auto" aria-hidden="true"></i>
                </a>`).join('');
        }
        results.classList.remove('d-none');
    };

    opener?.addEventListener('click', () => { open(); render(); });
    closer?.addEventListener('click', close);
    input.addEventListener('input', render);
    input.addEventListener('keydown', event => {
        if (event.key === 'Escape') { event.preventDefault(); close(); }
        if (event.key === 'Enter') {
            const first = results.querySelector('[data-search-result="0"]');
            if (first) { event.preventDefault(); window.location.href = first.href; }
        }
        if (event.key === 'ArrowDown') {
            const first = results.querySelector('[data-search-result="0"]');
            if (first) { event.preventDefault(); first.focus(); }
        }
    });
    results.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
    document.addEventListener('keydown', event => {
        const editing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)
            || document.activeElement?.isContentEditable;
        if ((event.ctrlKey && event.key.toLowerCase() === 'k') || (!editing && event.key === '/')) {
            event.preventDefault();
            open();
            render();
        }
    });
});

document.addEventListener('keydown', function(e) {
    const tag = document.activeElement.tagName.toLowerCase();

    if (tag === 'input' || tag === 'textarea' || tag === 'select') {
        return;
    }

    const shortcuts = @json(collect($erpShortcuts)->keyBy('key')->map(fn ($shortcut) => [
        'url' => $shortcut['url'] ?? null,
        'action' => $shortcut['action'] ?? null,
    ]));
    const shortcut = shortcuts[e.key];

    if (!shortcut) {
        return;
    }

    e.preventDefault();

    if (shortcut.action === 'priceLookup') {
        openGlobalPriceLookup();
        return;
    }

    if (shortcut.url) {
        window.location.href = shortcut.url;
    }
});
</script>
