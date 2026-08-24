{{-- Layout: Documento HTML principal. Define metadatos, estilos y scripts comunes de toda la aplicación. --}}
<!DOCTYPE html>
<html lang="es" class="layout-menu-fixed layout-compact" data-assets-path="{{ asset('/assets') . '/' }}" dir="ltr" data-skin="default" data-base-url="{{ url('/') }}" data-framework="laravel" data-bs-theme="light" data-template="vertical-menu-template">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>
        ORBY ERP - @yield('title', 'ORBY ERP')
    </title>
    <meta name="description" content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
    <meta name="keywords" content="{{ config('variables.templateKeyword') ? config('variables.templateKeyword') : '' }}" />
    <meta property="og:title" content="{{ config('variables.ogTitle') ? config('variables.ogTitle') : '' }}" />
    <meta property="og:type" content="{{ config('variables.ogType') ? config('variables.ogType') : '' }}" />
    <meta property="og:url" content="{{ config('variables.productPage') ? config('variables.productPage') : '' }}" />
    <meta property="og:image" content="{{ config('variables.ogImage') ? config('variables.ogImage') : '' }}" />
    <meta property="og:description" content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
    <meta property="og:site_name" content="{{ config('variables.creatorName') ? config('variables.creatorName') : '' }}" />
    <meta name="robots" content="noindex, nofollow" />
    <!-- laravel CRUD token -->
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <script>
        window.APP_BASE_URL = @json(rtrim(config('app.url'), '/'));
        window.ERP_PRINTING = @json([
            'printerName' => config('printing.thermal_printer_name'),
            'paperWidthMm' => config('printing.thermal_paper_width_mm'),
        ]);
    </script>
    <script>
        // Se ejecuta antes de cargar el CSS para evitar un destello claro al navegar.
        (() => {
            const key = 'erp.color-theme';
            const preference = localStorage.getItem(key) || 'system';
            const dark = preference === 'dark' ||
                (preference === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.dataset.bsTheme = dark ? 'dark' : 'light';
            document.documentElement.dataset.colorTheme = preference;
        })();
    </script>
    <script>
        // Las API del ERP usan la misma sesión web. Agrega CSRF automáticamente
        // a todas las escrituras hechas con fetch para evitar errores 419.
        (() => {
            const originalFetch = window.fetch.bind(window);
            const paginatedIndexes = {
                '/demo/sales/budgets': '/api/budgets',
                '/demo/sales/debit-notes': '/api/debit-notes',
                '/demo/sales/credit-notes': '/api/credit-notes',
                '/demo/sales/delivery-notes': '/api/delivery-notes',
                '/demo/sales/customer-notes': '/api/customer-notes',
                '/demo/purchases': '/api/purchases',
                '/demo/purchase-orders': '/api/purchase-orders',
                '/demo/provider-delivery-notes': '/api/provider-delivery-notes',
                '/demo/misc-expenses': '/api/misc-expenses',
                '/demo/expense-types': '/api/expense-types',
                '/demo/provider-payment-orders': '/api/provider-payment-orders',
                '/demo/stock/inventory': '/api/inventory-items',
                '/demo/size-types': '/api/size-types',
                '/demo/product-models': '/api/product-models',
                '/demo/brands': '/api/brands',
                '/demo/colors': '/api/colors',
                '/demo/sizes': '/api/sizes',
                '/demo/product-categories': '/api/product-categories',
                '/demo/stock-adjustment-reasons': '/api/stock-adjustment-reasons',
                '/demo/credit-note-reasons': '/api/credit-note-reasons',
                '/demo/finance/bank-movements': '/api/bank-movements',
                '/demo/finance/bank-accounts': '/api/bank-accounts',
                '/demo/finance/banks': '/api/banks',
                '/demo/finance/bank-branches': '/api/bank-branches',
                '/demo/finance/bank-concept-types': '/api/bank-concept-types',
                '/demo/finance/cash-concept-types': '/api/cash-concept-types',
                '/demo/finance/checkbooks': '/api/checkbooks',
                '/demo/finance/cash-boxes': '/api/cash-boxes',
                '/demo/finance/credit-cards': '/api/credit-cards',
                '/demo/finance/card-coupons': '/api/card-coupons',
                '/demo/finance/third-party-checks': '/api/third-party-checks',
                '/demo/finance/third-party-checks/operations': '/api/third-party-checks',
                '/demo/finance/exchange-rates': '/api/exchange-rates',
            };

            const appBasePath = new URL(window.APP_BASE_URL).pathname.replace(/\/$/, '');
            const normalizedPath = window.location.pathname
                .slice(appBasePath.length)
                .replace(/\/$/, '') || '/';
            const paginatedEndpoint = paginatedIndexes[normalizedPath];

            const renderAutomaticPagination = data => {
                if (!data || !Array.isArray(data.data) || !data.current_page) return;
                const table = document.querySelector('.card .table');
                const card = table?.closest('.card');
                if (!card) return;

                const paginationComponent = window.createApiPagination(card);
                window.renderApiPagination(data, page => {
                    const url = new URL(window.location.href);
                    url.searchParams.set('page', page);
                    window.location.href = url.toString();
                }, paginationComponent);
            };

            window.fetch = (resource, options = {}) => {
                const method = String(options.method ?? 'GET').toUpperCase();
                const url = typeof resource === 'string' ? resource : resource.url;
                const target = new URL(url, window.location.origin);

                const isPaginatedIndexRequest = method === 'GET' &&
                    paginatedEndpoint &&
                    target.origin === window.location.origin &&
                    target.pathname === `${appBasePath}${paginatedEndpoint}`;

                if (isPaginatedIndexRequest && !target.searchParams.has('page')) {
                    const requestedPage = new URL(window.location.href).searchParams.get('page');
                    if (requestedPage) target.searchParams.set('page', requestedPage);
                    resource = target.toString();
                }

                if (target.origin === window.location.origin && !['GET', 'HEAD', 'OPTIONS'].includes(method)) {
                    const headers = new Headers(options.headers ?? {});
                    const token = document.querySelector('meta[name="csrf-token"]')?.content;

                    if (token && !headers.has('X-CSRF-TOKEN')) {
                        headers.set('X-CSRF-TOKEN', token);
                    }

                    options = {
                        ...options,
                        headers
                    };
                }

                return originalFetch(resource, options).then(response => {
                    if (isPaginatedIndexRequest && response.ok) {
                        response.clone().json().then(renderAutomaticPagination).catch(() => {});
                    }
                    return response;
                });
            };
        })();

        window.formatDateTime = value => {
            if (!value) return '-';
            const source = String(value).trim();
            const compactArcaDate = source.match(/^(\d{4})(\d{2})(\d{2})$/);
            if (compactArcaDate) {
                return `${compactArcaDate[3]}/${compactArcaDate[2]}/${compactArcaDate[1]}`;
            }

            // Laravel serializa los datetime de Eloquent en UTC (sufijo Z).
            // Si el valor incluye zona horaria, convertirlo a la zona configurada
            // de la aplicacion antes de mostrarlo. Los datetime-local y las fechas
            // sin zona se mantienen literales para no alterar lo ingresado.
            if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?(?:Z|[+-]\d{2}:?\d{2})$/i.test(source)) {
                const parsed = new Date(source);

                if (!Number.isNaN(parsed.getTime())) {
                    return new Intl.DateTimeFormat('es-AR', {
                        timeZone: @json(config('app.timezone')),
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false,
                    }).format(parsed).replace(',', '');
                }
            }

            const match = source.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T\s](\d{2}):(\d{2}))?/);
            if (!match) return source;

            const date = `${match[3]}/${match[2]}/${match[1]}`;
            return match[4] !== undefined ? `${date} ${match[4]}:${match[5]}` : date;
        };
    </script>
    <!-- Canonical SEO -->
    <link rel="canonical" href="{{ config('variables.productPage') ? config('variables.productPage') : '' }}" />
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />

    <!-- Include Styles -->
    @include('layouts/sections/styles')

    <!-- Include Scripts for customizer, helper, analytics, config -->
    @include('layouts/sections/scriptsIncludes')
    @include('components.remote-select-script')
    @include('components.api-pagination-script')
    @include('components.stock-location-script')
</head>

<body>
    <!-- Layout Content -->
    @yield('layoutContent')
    <!--/ Layout Content -->

    @auth
    @include('components.global-price-lookup')
    @endauth

    <!-- Include Scripts -->
    @include('layouts/sections/scripts')
</body>

</html>
