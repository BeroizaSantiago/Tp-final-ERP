{{-- Componente: Submenú lateral. Renderiza las opciones anidadas del menú. --}}
<ul class="menu-sub">
  @if (isset($menu))
    @foreach ($menu as $submenu)

      @php
        $isActive = function ($item) use (&$isActive, $selectedMenuUrl) {
            if (isset($item->url)) {
                $url = trim($item->url, '/');
                return $selectedMenuUrl !== null && $url === $selectedMenuUrl;
            }

            if (isset($item->submenu)) {
                foreach ($item->submenu as $child) {
                    if ($isActive($child)) {
                        return true;
                    }
                }
            }

            return false;
        };

        $activeClass = $isActive($submenu)
            ? (isset($submenu->submenu) ? 'active open' : 'active')
            : null;
      @endphp

      <li class="menu-item {{ $activeClass }}">
        <a
          href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}"
          class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
          @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif
        >
          @if (isset($submenu->icon))
            <i class="{{ $submenu->icon }}"></i>
          @endif

          <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>

          @isset($submenu->badge)
            <div class="badge bg-{{ $submenu->badge[0] }} rounded-pill ms-auto">
              {{ $submenu->badge[1] }}
            </div>
          @endisset
        </a>

        @if (isset($submenu->submenu))
          @include('layouts.sections.menu.submenu', ['menu' => $submenu->submenu, 'selectedMenuUrl' => $selectedMenuUrl])
        @endif
      </li>

    @endforeach
  @endif
</ul>
