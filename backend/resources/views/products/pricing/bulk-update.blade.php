{{-- Vista: Precios de Productos. Muestra la pantalla o componente funcional correspondiente a Precios de Productos. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Actualización masiva de precios</h4>
        <small class="text-muted">
            Simulá y aplicá cambios porcentuales sobre múltiples productos
        </small>
    </div>

    <a href="{{ url('/demo/products') }}" class="btn btn-secondary">
        <i class="ri-arrow-left-line me-1"></i>
        Volver
    </a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-1">¿Qué precios actualizar?</h5>
        <small class="text-muted">
            Definí la lista, el porcentaje y la forma de aplicar el cambio
        </small>
    </div>

    <div class="card-body">
        <form id="simulationForm">

            <div class="row">

                <div class="col-lg-3 col-md-6 mb-3">
                    <label class="form-label">Lista de precio *</label>

                    <select
                        class="form-select"
                        id="price_list"
                        name="price_list"
                        required
                    >
                        <option value="all">Todos los precios de venta</option>
                        <option value="a">Precio A</option>
                        <option value="b">Precio B</option>
                        <option value="c">Precio C</option>
                        <option value="d">Precio D</option>
                    </select>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <label class="form-label">Tipo de precio *</label>

                    <select
                        class="form-select"
                        id="price_type"
                        name="price_type"
                        required
                    >
                        <option value="with_tax">Precio final con IVA</option>
                        <option value="net">Precio sin IVA</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4 mb-3">
                    <label class="form-label">Porcentaje *</label>

                    <div class="input-group">
                        <input
                            class="form-control"
                            id="percentage"
                            name="percentage"
                            type="number"
                            min="0"
                            step="0.01"
                            value="0"
                            required
                        >

                        <span class="input-group-text">%</span>
                    </div>
                </div>

                <div class="col-lg-2 col-md-4 mb-3">
                    <label class="form-label">Operación *</label>

                    <select
                        class="form-select"
                        id="operation"
                        name="operation"
                        required
                    >
                        <option value="add">Sumar</option>
                        <option value="subtract">Restar</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4 mb-3">
                    <label class="form-label">Decimales</label>

                    <select
                        class="form-select"
                        id="round_decimals"
                        name="round_decimals"
                    >
                        <option value="0">Sin decimales</option>
                        <option value="1">1 decimal</option>
                        <option value="2" selected>2 decimales</option>
                        <option value="3">3 decimales</option>
                        <option value="4">4 decimales</option>
                    </select>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <label class="form-label">Dirección del redondeo</label>

                    <select
                        class="form-select"
                        id="round_direction"
                        name="round_direction"
                    >
                        <option value="normal">Redondeo normal</option>
                        <option value="up">Siempre hacia arriba</option>
                        <option value="down">Siempre hacia abajo</option>
                    </select>
                </div>

            </div>

        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-1">¿Cuáles productos actualizar?</h5>
        <small class="text-muted">
            Dejá los filtros vacíos para aplicar el cambio a todos los productos
        </small>
    </div>

    <div class="card-body">
        <div class="row">

            <div class="col-lg-3 col-md-6 mb-3">
                <label class="form-label">Categoría</label>

                <select
                    class="form-select"
                    id="category_id"
                    name="category_id"
                >
                    <option value="">Todas las categorías</option>
                </select>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <label class="form-label">Marca</label>

                <select
                    class="form-select"
                    id="brand_id"
                    name="brand_id"
                >
                    <option value="">Todas las marcas</option>
                </select>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <label class="form-label">Modelo</label>

                <select
                    class="form-select"
                    id="product_model_id"
                    name="product_model_id"
                >
                    <option value="">Todos los modelos</option>
                </select>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <label class="form-label">Moneda</label>

                <select
                    class="form-select"
                    id="currency_name"
                    name="currency_name"
                >
                    <option value="">Todas las monedas</option>
                    <option value="Pesos">Pesos</option>
                    <option value="Dólares">Dólares</option>
                </select>
            </div>

            <div class="col-lg-4 col-md-6 mb-3">
                <label class="form-label">Producto específico</label>

                <select
                    class="form-select"
                    id="product_id"
                    name="product_id"
                    data-remote-url="{{ url('/api/products') }}"
                    data-remote-placeholder="Buscar producto (mín. 3 caracteres)"
                >
                    <option value="">Todos los productos</option>
                </select>
            </div>

            <div class="col-lg-4 col-md-6 mb-3">
                <label class="form-label">Buscar por nombre o código</label>

                <input
                    class="form-control"
                    id="search"
                    name="search"
                    placeholder="Nombre, código, barras o referencia..."
                >
            </div>

            <div class="col-lg-4 mb-3 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">

                    <button
                        type="button"
                        class="btn btn-outline-secondary flex-fill"
                        onclick="clearFilters()"
                    >
                        <i class="ri-filter-off-line me-1"></i>
                        Limpiar filtros
                    </button>

                    <button
                        type="button"
                        id="simulateButton"
                        class="btn btn-primary flex-fill"
                        onclick="simulatePrices()"
                    >
                        <i class="ri-search-eye-line me-1"></i>
                        Simular precios
                    </button>

                </div>
            </div>

        </div>
    </div>
</div>

<div id="summaryBox" class="d-none mb-4"></div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h5 class="mb-1">Simulación de actualización</h5>
            <small class="text-muted">
                Comparación entre los valores anteriores y los nuevos precios
            </small>
        </div>

        <div class="d-flex gap-2">
            <button
                type="button"
                id="resetButton"
                class="btn btn-outline-secondary d-none"
                onclick="resetSimulation()"
            >
                <i class="ri-refresh-line me-1"></i>
                Procesar de nuevo
            </button>

            <button
                type="button"
                id="applyButton"
                class="btn btn-success"
                onclick="applyPrices()"
                disabled
            >
                <i class="ri-save-line me-1"></i>
                Guardar cambios
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Clasificación</th>
                    <th>Lista</th>
                    <th>Campo</th>
                    <th class="text-end">Anterior</th>
                    <th class="text-end">Nuevo</th>
                    <th class="text-end">Diferencia</th>
                    <th class="text-end">Variación</th>
                </tr>
            </thead>

            <tbody id="previewRows">
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">
                        Todavía no se realizó ninguna simulación.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card-footer d-none flex-wrap justify-content-between align-items-center gap-3" id="previewPaginationBox">
        <div class="d-flex align-items-center gap-2">
            <label for="previewPageSize" class="text-muted mb-0">Mostrar</label>
            <select id="previewPageSize" class="form-select form-select-sm" style="width:auto">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span class="text-muted">por página</span>
        </div>

        <div class="d-flex align-items-center gap-3">
            <small class="text-muted" id="previewRange"></small>
            <nav aria-label="Paginación de la simulación">
                <ul class="pagination pagination-sm mb-0" id="previewPagination"></ul>
            </nav>
        </div>
    </div>
</div>

<script>
let simulationToken = null;
let simulationData = null;
let previewPage = 1;

function money(value) {
    return Number(value ?? 0).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS'
    });
}

function percentage(value) {
    if (value === null || value === undefined) {
        return '-';
    }

    const number = Number(value);

    return `${number > 0 ? '+' : ''}${number.toFixed(2)}%`;
}

function priceFieldLabel(field) {
    const labels = {
        price_a: 'Sin IVA',
        price_a_with_tax: 'Con IVA',
        price_b: 'Sin IVA',
        price_b_with_tax: 'Con IVA',
        price_c: 'Sin IVA',
        price_c_with_tax: 'Con IVA',
        price_d: 'Sin IVA',
        price_d_with_tax: 'Con IVA'
    };

    return labels[field] ?? field;
}

function differenceBadge(value) {
    const number = Number(value ?? 0);

    if (number > 0) {
        return `
            <span class="badge bg-label-danger">
                +${money(number)}
            </span>
        `;
    }

    if (number < 0) {
        return `
            <span class="badge bg-label-success">
                ${money(number)}
            </span>
        `;
    }

    return `
        <span class="badge bg-label-secondary">
            Sin cambios
        </span>
    `;
}

async function cargarSelect(url, selectId, emptyLabel) {
    const select = document.getElementById(selectId);

    try {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json'
            }
        });

        const json = await response.json();
        const items = json.data ?? json;

        select.innerHTML = `<option value="">${emptyLabel}</option>`;

        items.forEach(item => {
            const label =
                item.name ??
                item.description ??
                `ID ${item.id}`;

            select.innerHTML += `
                <option value="${item.id}">
                    ${label}
                </option>
            `;
        });

    } catch (error) {
        console.error(`Error cargando ${selectId}:`, error);

        select.innerHTML = `
            <option value="">
                Error al cargar
            </option>
        `;
    }
}

async function cargarProductos() {
    const select = document.getElementById('product_id');
    erpEnhanceRemoteSelect(select);
}

function getPayload() {
    return {
        price_list: document.getElementById('price_list').value,
        price_type: document.getElementById('price_type').value,
        percentage: Number(
            document.getElementById('percentage').value || 0
        ),
        operation: document.getElementById('operation').value,
        round_decimals: Number(
            document.getElementById('round_decimals').value || 2
        ),
        round_direction:
            document.getElementById('round_direction').value,

        category_id:
            document.getElementById('category_id').value || null,

        brand_id:
            document.getElementById('brand_id').value || null,

        product_model_id:
            document.getElementById('product_model_id').value || null,

        currency_name:
            document.getElementById('currency_name').value || null,

        product_id:
            document.getElementById('product_id').value || null,

        search:
            document.getElementById('search').value.trim() || null
    };
}

function renderSummary(summary) {
    const box = document.getElementById('summaryBox');

    box.classList.remove('d-none');

    box.innerHTML = `
        <div class="row">

            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-muted">Productos afectados</small>
                        <h3 class="mb-0">
                            ${summary.products ?? 0}
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-muted">Precios que aumentan</small>
                        <h3 class="mb-0 text-danger">
                            ${summary.increases ?? 0}
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-muted">Precios que disminuyen</small>
                        <h3 class="mb-0 text-success">
                            ${summary.decreases ?? 0}
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-muted">Variación promedio</small>
                        <h3 class="mb-0">
                            ${percentage(summary.average_variation)}
                        </h3>
                    </div>
                </div>
            </div>

        </div>
    `;
}

function renderPreview(items) {
    const rows = document.getElementById('previewRows');
    const paginationBox = document.getElementById('previewPaginationBox');
    const flattened = [];

    items.forEach(product => {
        (product.changes ?? []).forEach(change => {
            flattened.push({
                ...product,
                change
            });
        });
    });

    if (!flattened.length) {
        rows.innerHTML = `
            <tr>
                <td colspan="9" class="text-center text-muted py-5">
                    No se encontraron productos para actualizar.
                </td>
            </tr>
        `;

        paginationBox.classList.add('d-none');
        paginationBox.classList.remove('d-flex');
        return;
    }

    const pageSize = Number(document.getElementById('previewPageSize').value || 25);
    const totalPages = Math.max(1, Math.ceil(flattened.length / pageSize));
    previewPage = Math.min(Math.max(previewPage, 1), totalPages);
    const start = (previewPage - 1) * pageSize;

    rows.innerHTML = flattened.slice(start, start + pageSize).map(item => `
        <tr>
            <td>
                <span class="badge bg-label-secondary">
                    ${item.code ?? '-'}
                </span>
            </td>

            <td>
                <strong>${item.name ?? '-'}</strong>
                <br>
                <small class="text-muted">
                    ID local: ${item.product_id}
                </small>
            </td>

            <td>
                <small>
                    ${item.category ?? 'Sin categoría'}
                    <br>
                    ${item.brand ?? 'Sin marca'}
                    ${item.model ? ` · ${item.model}` : ''}
                </small>
            </td>

            <td>
                <span class="badge bg-label-primary">
                    Precio ${item.change.list}
                </span>
            </td>

            <td>
                ${priceFieldLabel(item.change.field)}
            </td>

            <td class="text-end">
                ${money(item.change.old_value)}
            </td>

            <td class="text-end">
                <strong>
                    ${money(item.change.new_value)}
                </strong>
            </td>

            <td class="text-end">
                ${differenceBadge(item.change.difference)}
            </td>

            <td class="text-end">
                ${percentage(item.change.difference_percentage)}
            </td>
        </tr>
    `).join('');

    document.getElementById('previewRange').textContent =
        `${start + 1}-${Math.min(start + pageSize, flattened.length)} de ${flattened.length}`;

    const pages = [];
    for (let page = Math.max(1, previewPage - 2); page <= Math.min(totalPages, previewPage + 2); page++) {
        pages.push(page);
    }

    document.getElementById('previewPagination').innerHTML = `
        <li class="page-item ${previewPage === 1 ? 'disabled' : ''}">
            <button class="page-link" type="button" data-preview-page="${previewPage - 1}" aria-label="Anterior">&lsaquo;</button>
        </li>
        ${pages.map(page => `
            <li class="page-item ${page === previewPage ? 'active' : ''}">
                <button class="page-link" type="button" data-preview-page="${page}">${page}</button>
            </li>
        `).join('')}
        <li class="page-item ${previewPage === totalPages ? 'disabled' : ''}">
            <button class="page-link" type="button" data-preview-page="${previewPage + 1}" aria-label="Siguiente">&rsaquo;</button>
        </li>
    `;

    paginationBox.classList.remove('d-none');
    paginationBox.classList.add('d-flex');
}

function setSimulatingState(active) {
    const button = document.getElementById('simulateButton');

    button.disabled = active;

    button.innerHTML = active
        ? `
            <span class="spinner-border spinner-border-sm me-1"></span>
            Simulando...
        `
        : `
            <i class="ri-search-eye-line me-1"></i>
            Simular precios
        `;
}

document.getElementById('previewPageSize').addEventListener('change', () => {
    previewPage = 1;
    renderPreview(simulationData?.preview ?? []);
});

document.getElementById('previewPagination').addEventListener('click', event => {
    const button = event.target.closest('[data-preview-page]');
    if (!button || button.closest('.page-item')?.classList.contains('disabled')) {
        return;
    }

    previewPage = Number(button.dataset.previewPage);
    renderPreview(simulationData?.preview ?? []);
    document.getElementById('previewRows').closest('.card').scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });
});

async function simulatePrices() {
    const payload = getPayload();

    if (payload.percentage <= 0) {
        alert('Ingresá un porcentaje mayor a cero.');
        return;
    }

    const hasFilters =
        payload.category_id ||
        payload.brand_id ||
        payload.product_model_id ||
        payload.currency_name ||
        payload.product_id ||
        payload.search;

    if (!hasFilters) {
        const confirmed = await window.erpConfirm(
            'No seleccionaste ningún filtro.\n\n' +
            'La simulación se aplicará sobre todos los productos.\n\n' +
            '¿Querés continuar?'
        );

        if (!confirmed) {
            return;
        }
    }

    simulationToken = null;
    simulationData = null;

    document.getElementById('applyButton').disabled = true;
    document.getElementById('resetButton').classList.add('d-none');

    setSimulatingState(true);

    try {
        const response = await fetch(`${window.APP_BASE_URL}/api/product-bulk-price-update/simulate`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify(payload)
            }
        );

        const data = await response.json();

        if (!response.ok) {
            const errors = data.errors
                ? Object.values(data.errors).flat().join('\n')
                : '';

            alert([
                data.message ?? 'No se pudo realizar la simulación.',
                errors
            ].filter(Boolean).join('\n'));

            return;
        }

        simulationToken = data.token;
        simulationData = data;
        previewPage = 1;

        renderSummary(data.summary ?? {});
        renderPreview(data.preview ?? []);

        document
            .getElementById('resetButton')
            .classList.remove('d-none');

        document.getElementById('applyButton').disabled =
            !(data.preview ?? []).length;

    } catch (error) {
        console.error(error);
        alert('No se pudo conectar con el servidor.');
    } finally {
        setSimulatingState(false);
    }
}

async function applyPrices() {
    if (!simulationToken) {
        alert('Primero debés simular la actualización.');
        return;
    }

    const products = simulationData?.summary?.products ?? 0;
    const changes = simulationData?.summary?.price_changes ?? 0;

    const confirmed = await window.erpConfirm(
        `Se actualizarán ${products} producto(s) y ${changes} precio(s).\n\n` +
        '¿Confirmás que querés aplicar los cambios?'
    );

    if (!confirmed) {
        return;
    }

    const button = document.getElementById('applyButton');

    button.disabled = true;
    button.innerHTML = `
        <span class="spinner-border spinner-border-sm me-1"></span>
        Guardando...
    `;

    try {
        const response = await fetch(`${window.APP_BASE_URL}/api/product-bulk-price-update/apply`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify({
                    token: simulationToken
                })
            }
        );

        const data = await response.json();

        if (!response.ok) {
            alert(
                data.message ??
                'No se pudieron actualizar los precios.'
            );

            button.disabled = false;
            button.innerHTML = `
                <i class="ri-save-line me-1"></i>
                Guardar cambios
            `;

            return;
        }

        alert(
            `${data.message}\n\n` +
            `Productos actualizados: ${data.updated_products ?? 0}\n` +
            `Precios actualizados: ${data.updated_prices ?? 0}`
        );

        simulationToken = null;

        button.disabled = true;
        button.innerHTML = `
            <i class="ri-check-line me-1"></i>
            Cambios aplicados
        `;

    } catch (error) {
        console.error(error);
        alert('No se pudo conectar con el servidor.');

        button.disabled = false;
        button.innerHTML = `
            <i class="ri-save-line me-1"></i>
            Guardar cambios
        `;
    }
}

function clearFilters() {
    document.getElementById('category_id').value = '';
    document.getElementById('brand_id').value = '';
    document.getElementById('product_model_id').value = '';
    document.getElementById('currency_name').value = '';
    document.getElementById('product_id').value = '';
    document.getElementById('search').value = '';
}

function resetSimulation() {
    simulationToken = null;
    simulationData = null;
    previewPage = 1;

    document
        .getElementById('summaryBox')
        .classList.add('d-none');

    document.getElementById('summaryBox').innerHTML = '';

    document.getElementById('previewRows').innerHTML = `
        <tr>
            <td colspan="9" class="text-center text-muted py-5">
                Todavía no se realizó ninguna simulación.
            </td>
        </tr>
    `;

    document.getElementById('previewPaginationBox').classList.add('d-none');
    document.getElementById('previewPaginationBox').classList.remove('d-flex');

    document.getElementById('applyButton').disabled = true;
    document.getElementById('applyButton').innerHTML = `
        <i class="ri-save-line me-1"></i>
        Guardar cambios
    `;

    document
        .getElementById('resetButton')
        .classList.add('d-none');
}

document.addEventListener('DOMContentLoaded', async () => {
    await Promise.all([
        cargarSelect(
            `${window.APP_BASE_URL}/api/product-categories?lookup=1`,
            'category_id',
            'Todas las categorías'
        ),

        cargarSelect(
            `${window.APP_BASE_URL}/api/brands?lookup=1`,
            'brand_id',
            'Todas las marcas'
        ),

        cargarSelect(
            `${window.APP_BASE_URL}/api/product-models?lookup=1`,
            'product_model_id',
            'Todos los modelos'
        ),

        cargarProductos()
    ]);
});
</script>

@endsection
