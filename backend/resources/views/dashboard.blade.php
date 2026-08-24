{{-- Dashboard ejecutivo del ERP. Los indicadores se obtienen desde un endpoint agregado. --}}
@extends('layouts.app')

@section('title', 'Dashboard')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('content')
<div class="dashboard-shell">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-label-primary">Panel ejecutivo</span>
                <small class="text-muted" id="dashboardUpdatedAt">Actualizando…</small>
            </div>
            <h3 class="mb-1">Resumen del negocio</h3>
            <p class="text-muted mb-0">Ventas, inventario y salud del catálogo en una sola vista.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" id="refreshDashboard">
                <i class="icon-base ri ri-refresh-line me-1"></i>Actualizar
            </button>
        </div>
    </div>

    <div id="dashboardError" class="alert alert-danger d-none"></div>

    <div class="row g-4 mb-4" id="dashboardKpis">
        @foreach([
            ['salesToday', 'Ventas de hoy', 'ri-money-dollar-circle-line', 'primary'],
            ['salesMonth', 'Ventas del mes', 'ri-line-chart-line', 'success'],
            ['averageTicket', 'Ticket promedio', 'ri-receipt-line', 'info'],
            ['stockValue', 'Stock valorizado', 'ri-stack-line', 'warning'],
        ] as [$id, $label, $icon, $color])
        <div class="col-sm-6 col-xl-3">
            <div class="card dashboard-kpi-card h-100">
                <div class="card-body d-flex align-items-start justify-content-between">
                    <div>
                        <small class="text-muted d-block mb-2">{{ $label }}</small>
                        <h4 class="mb-1 dashboard-kpi-value" id="{{ $id }}">—</h4>
                        <small class="text-muted" id="{{ $id }}Meta">Cargando datos</small>
                    </div>
                    <span class="avatar avatar-md bg-label-{{ $color }} rounded">
                        <i class="icon-base ri {{ $icon }} icon-md"></i>
                    </span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h5 class="mb-1">Evolución de ventas</h5>
                        <small class="text-muted">Facturación diaria de los últimos 30 días</small>
                    </div>
                    <span class="badge bg-label-success" id="salesThirtyDays">—</span>
                </div>
                <div class="card-body pt-0">
                    <div id="salesTrendChart" class="dashboard-chart"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-1">Pulso operativo</h5>
                    <small class="text-muted">Indicadores para actuar hoy</small>
                </div>
                <div class="card-body">
                    <div class="dashboard-pulse-item">
                        <span class="avatar bg-label-primary rounded"><i class="icon-base ri ri-shopping-cart-line"></i></span>
                        <div><small class="text-muted">Operaciones de hoy</small><h5 class="mb-0" id="transactionsToday">—</h5></div>
                    </div>
                    <div class="dashboard-pulse-item">
                        <span class="avatar bg-label-warning rounded"><i class="icon-base ri ri-alarm-warning-line"></i></span>
                        <div><small class="text-muted">Ítems en stock mínimo</small><h5 class="mb-0" id="lowStockItems">—</h5></div>
                    </div>
                    <div class="dashboard-pulse-item">
                        <span class="avatar bg-label-info rounded"><i class="icon-base ri ri-box-3-line"></i></span>
                        <div><small class="text-muted">Productos activos</small><h5 class="mb-0" id="activeProducts">—</h5></div>
                    </div>
                    <div class="dashboard-pulse-item mb-0">
                        <span class="avatar bg-label-success rounded"><i class="icon-base ri ri-group-line"></i></span>
                        <div><small class="text-muted">Clientes / Proveedores</small><h5 class="mb-0" id="businessContacts">—</h5></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h5 class="mb-1">Clasificación de productos</h5>
                <small class="text-muted">Cada producto recibe una única etiqueta según la primera condición aplicable.</small>
            </div>
            <ul class="nav nav-pills" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#classificationProducts" type="button">
                        <i class="icon-base ri ri-price-tag-3-line me-1"></i>Productos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#classificationStock" type="button">
                        <i class="icon-base ri ri-stack-line me-1"></i>Stock
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content p-0">
                <div class="tab-pane fade show active" id="classificationProducts">
                    <div class="row align-items-center g-4">
                        <div class="col-lg-5">
                            <div id="classificationProductsChart" class="dashboard-chart"></div>
                        </div>
                        <div class="col-lg-7"><div class="row g-3" id="classificationCards"></div></div>
                    </div>
                </div>
                <div class="tab-pane fade" id="classificationStock">
                    <div class="row align-items-center g-4">
                        <div class="col-lg-7">
                            <div id="classificationStockChart" class="dashboard-chart"></div>
                        </div>
                        <div class="col-lg-5"><div id="classificationStockSummary"></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-1">Productos más vendidos</h5>
                    <small class="text-muted">Unidades vendidas en los últimos 30 días</small>
                </div>
                <div class="card-body pt-0"><div id="topProductsChart" class="dashboard-chart"></div></div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div><h5 class="mb-1">Stock que requiere atención</h5><small class="text-muted">Durmientes y muertos, priorizados por valorización</small></div>
                    <a href="{{ url('/demo/stock/inventory') }}" class="btn btn-sm btn-outline-primary">Ver inventario</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Producto</th><th>Estado</th><th class="text-end">Unidades</th><th class="text-end">Valorizado</th><th>Última venta</th></tr></thead>
                        <tbody id="attentionProducts"><tr><td colspan="5" class="text-center text-muted py-5">Cargando…</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const endpoint = `${window.APP_BASE_URL}/api/dashboard`;
    const colors = ['#8c57ff', '#56ca00', '#ffb400', '#ff4c51'];
    const classificationMeta = {
        new: {label: 'Nuevo', color: 'primary', icon: 'ri-sparkling-line'},
        alive: {label: 'Vivo', color: 'success', icon: 'ri-pulse-line'},
        dormant: {label: 'Durmiente', color: 'warning', icon: 'ri-moon-line'},
        dead: {label: 'Muerto', color: 'danger', icon: 'ri-alert-line'}
    };
    let charts = [];
    let lastDashboardData = null;

    const money = value => Number(value ?? 0).toLocaleString('es-AR', {style: 'currency', currency: 'ARS', maximumFractionDigits: 0});
    const number = value => Number(value ?? 0).toLocaleString('es-AR', {maximumFractionDigits: 2});
    const setText = (id, value) => { const element = document.getElementById(id); if (element) element.textContent = value; };

    function destroyCharts() {
        charts.forEach(chart => chart.destroy());
        charts = [];
    }

    function chart(element, options) {
        if (!document.querySelector(element)) return;
        const instance = new ApexCharts(document.querySelector(element), options);
        instance.render();
        charts.push(instance);
    }

    function baseChart(type, height = 300) {
        const styles = getComputedStyle(document.body);
        const textColor = styles.getPropertyValue('--bs-body-color').trim() || styles.color;
        const borderColor = styles.getPropertyValue('--bs-border-color').trim() || 'rgba(128,128,128,.18)';
        const mode = document.documentElement.dataset.bsTheme === 'dark' ? 'dark' : 'light';

        return {
            chart: {type, height, toolbar: {show: false}, fontFamily: 'Inter, sans-serif', background: 'transparent', foreColor: textColor},
            theme: {mode},
            dataLabels: {enabled: false},
            grid: {borderColor, strokeDashArray: 5},
            tooltip: {theme: mode}
        };
    }

    function render(data) {
        lastDashboardData = data;
        destroyCharts();
        const summary = data.summary;
        setText('salesToday', money(summary.sales_today));
        setText('salesTodayMeta', `${number(summary.transactions_today)} operaciones`);
        setText('salesMonth', money(summary.sales_month));
        setText('salesMonthMeta', 'Acumulado del mes');
        setText('averageTicket', money(summary.average_ticket_month));
        setText('averageTicketMeta', 'Promedio mensual');
        setText('stockValue', money(summary.stock_value));
        setText('stockValueMeta', `${number(summary.stock_units)} unidades`);
        setText('transactionsToday', number(summary.transactions_today));
        setText('lowStockItems', number(summary.low_stock_items));
        setText('activeProducts', number(summary.active_products));
        setText('businessContacts', `${number(summary.clients)} / ${number(summary.providers)}`);
        setText('dashboardUpdatedAt', `Actualizado ${new Date(data.generated_at).toLocaleTimeString('es-AR', {hour: '2-digit', minute: '2-digit'})}`);
        setText('salesThirtyDays', money(data.sales.totals.reduce((sum, value) => sum + Number(value), 0)));

        chart('#salesTrendChart', {
            ...baseChart('area', 315),
            series: [{name: 'Ventas', data: data.sales.totals}],
            colors: [colors[0]],
            stroke: {curve: 'smooth', width: 3},
            fill: {type: 'gradient', gradient: {shadeIntensity: 1, opacityFrom: .38, opacityTo: .04, stops: [0, 95]}},
            xaxis: {categories: data.sales.labels, labels: {rotate: 0, hideOverlappingLabels: true}},
            yaxis: {labels: {formatter: value => money(value)}},
            tooltip: {y: {formatter: value => money(value)}}
        });

        const keys = Object.keys(classificationMeta);
        const classValues = keys.map(key => data.classification[key].products);
        chart('#classificationProductsChart', {
            ...baseChart('donut', 320),
            series: classValues,
            labels: keys.map(key => classificationMeta[key].label),
            colors,
            legend: {
                show: true,
                position: 'top',
                horizontalAlign: 'center',
                onItemClick: {toggleDataSeries: true},
                onItemHover: {highlightDataSeries: true},
                markers: {offsetX: -2},
                itemMargin: {horizontal: 10, vertical: 6}
            },
            stroke: {width: 4, colors: ['var(--bs-card-bg)']},
            plotOptions: {pie: {donut: {size: '68%', labels: {show: true,
                name: {color: getComputedStyle(document.body).color},
                value: {color: getComputedStyle(document.body).color},
                total: {show: true, label: 'Productos visibles', color: getComputedStyle(document.body).color, formatter: chartContext => number(chartContext.globals.seriesTotals.reduce((sum, value) => sum + value, 0))}
            }}}}
        });

        classificationCards.innerHTML = keys.map(key => {
            const meta = classificationMeta[key];
            const item = data.classification[key];
            return `<div class="col-sm-6"><div class="classification-card border rounded p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2"><span class="avatar bg-label-${meta.color} rounded"><i class="icon-base ri ${meta.icon}"></i></span><span class="badge bg-label-${meta.color}">${number(item.products)}</span></div>
                <h6 class="mb-1">${meta.label}</h6><small class="text-muted d-block">${data.classification_definitions[key]}</small>
            </div></div>`;
        }).join('');

        chart('#classificationStockChart', {
            ...baseChart('bar', 320),
            series: [{name: 'Unidades', data: keys.map(key => data.classification[key].stock_units)}],
            colors,
            plotOptions: {bar: {horizontal: true, borderRadius: 7, distributed: true, barHeight: '55%'}},
            xaxis: {categories: keys.map(key => classificationMeta[key].label), labels: {formatter: value => number(value)}},
            legend: {show: false},
            tooltip: {y: {formatter: value => `${number(value)} unidades`}}
        });

        classificationStockSummary.innerHTML = keys.map(key => {
            const meta = classificationMeta[key]; const item = data.classification[key];
            return `<div class="d-flex align-items-center justify-content-between border-bottom py-3">
                <div class="d-flex align-items-center gap-3"><span class="dashboard-dot bg-${meta.color}"></span><div><strong>${meta.label}</strong><small class="text-muted d-block">${number(item.stock_units)} unidades</small></div></div>
                <strong>${money(item.stock_value)}</strong>
            </div>`;
        }).join('');

        const top = data.top_products ?? [];
        chart('#topProductsChart', {
            ...baseChart('bar', 350),
            series: [{name: 'Unidades', data: top.map(item => item.units)}],
            colors: [colors[1]],
            plotOptions: {bar: {horizontal: false, borderRadius: 7, columnWidth: '52%', endingShape: 'rounded'}},
            xaxis: {
                categories: top.map(item => item.name),
                labels: {
                    rotate: -35,
                    rotateAlways: top.length > 4,
                    trim: true,
                    maxHeight: 95,
                    formatter: value => value.length > 18 ? `${value.slice(0, 18)}…` : value
                }
            },
            yaxis: {labels: {formatter: value => number(value)}, min: 0},
            tooltip: {y: {formatter: value => `${number(value)} unidades`}}
        });

        attentionProducts.innerHTML = (data.attention_products ?? []).map(item => {
            const meta = classificationMeta[item.classification];
            const lastSale = item.last_sale_at ? new Date(`${item.last_sale_at}T00:00:00`).toLocaleDateString('es-AR') : 'Nunca';
            return `<tr><td><strong>${item.name}</strong><small class="text-muted d-block">${item.code ?? 'Sin código'}</small></td>
                <td><span class="badge bg-label-${meta.color}">${meta.label}</span></td><td class="text-end">${number(item.stock_units)}</td>
                <td class="text-end fw-medium">${money(item.stock_value)}</td><td>${lastSale}</td></tr>`;
        }).join('') || '<tr><td colspan="5" class="text-center text-muted py-5">No hay productos que requieran atención.</td></tr>';
    }

    async function loadDashboard() {
        refreshDashboard.disabled = true;
        refreshDashboard.querySelector('i').classList.add('dashboard-spin');
        dashboardError.classList.add('d-none');
        try {
            const response = await fetch(endpoint, {headers: {Accept: 'application/json'}});
            const data = await response.json();
            if (!response.ok) throw new Error(data.message ?? 'No se pudo cargar el dashboard.');
            render(data);
        } catch (error) {
            dashboardError.textContent = error.message;
            dashboardError.classList.remove('d-none');
        } finally {
            refreshDashboard.disabled = false;
            refreshDashboard.querySelector('i').classList.remove('dashboard-spin');
        }
    }

    refreshDashboard.addEventListener('click', loadDashboard);
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', () => setTimeout(() => window.dispatchEvent(new Event('resize')), 50));
    });
    window.addEventListener('erp:theme-changed', () => {
        if (lastDashboardData) requestAnimationFrame(() => render(lastDashboardData));
    });
    loadDashboard();
});
</script>
@endsection
