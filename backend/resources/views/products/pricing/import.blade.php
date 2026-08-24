{{-- Vista: Precios de Productos. Muestra la pantalla o componente funcional correspondiente a Precios de Productos. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Importación masiva de precios</h4>
        <small class="text-muted">
            Simulá los cambios antes de actualizar los precios de los productos
        </small>
    </div>

    <a href="{{ url('/demo/products') }}" class="btn btn-secondary">
        <i class="ri-arrow-left-line me-1"></i>
        Volver
    </a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-1">Configuración de la importación</h5>
        <small class="text-muted">
            El archivo debe contener las columnas <strong>codigo</strong> y
            <strong>precio</strong>.
        </small>
    </div>

    <div class="card-body">
        <form id="importForm" enctype="multipart/form-data">

            <div class="row">

                <div class="col-md-4 mb-3">
                    <label class="form-label">Tipo de identificador *</label>

                    <select
                        class="form-select"
                        id="identifier_type"
                        name="identifier_type"
                        required
                    >
                        <option value="code">Código interno</option>
                        <option value="bar_code">Código de barras del producto</option>
                        <option value="reference_code">Código de referencia</option>
                        <option value="variant_bar_code" selected>Código de barras de variante (recomendado)</option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Lista de precio *</label>

                    <select
                        class="form-select"
                        id="price_list"
                        name="price_list"
                        required
                    >
                        <option value="a">Precio A</option>
                        <option value="b">Precio B</option>
                        <option value="c">Precio C</option>
                        <option value="d">Precio D</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo de precio *</label>

                    <select
                        class="form-select"
                        id="price_type"
                        name="price_type"
                        required
                    >
                        <option value="net">Precio sin IVA</option>
                        <option value="with_tax">Precio final con IVA</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Cantidad de decimales</label>

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

                <div class="col-md-3 mb-3">
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

                <div class="col-md-6 mb-3">
                    <label class="form-label">Archivo Excel o CSV *</label>

                    <input
                        class="form-control"
                        id="file"
                        name="file"
                        type="file"
                        accept=".xlsx,.xls,.csv,.txt"
                        required
                    >

                    <small class="text-muted">
                        Formatos permitidos: XLSX, XLS, CSV o TXT.
                    </small>
                </div>

                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button
                        type="submit"
                        id="simulateButton"
                        class="btn btn-primary w-100"
                    >
                        <i class="ri-search-eye-line me-1"></i>
                        Simular
                    </button>
                </div>

            </div>

        </form>

        <div id="progressBox" class="d-none mb-4">
            <div class="d-flex justify-content-between mb-1">
                <span id="progressTitle">Procesando por bloques...</span>
                <span id="progressLabel">0%</span>
            </div>
            <div class="progress">
                <div id="progressBar" class="progress-bar" style="width:0%"></div>
            </div>
        </div>

        <div class="alert alert-light border mb-0">
            <strong>Formato esperado:</strong>

            <div class="table-responsive mt-2">
                <table class="table table-sm table-bordered mb-0" style="max-width:420px;">
                    <thead>
                        <tr>
                            <th>codigo</th>
                            <th>precio</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>REM-001</td>
                            <td>15000</td>
                        </tr>

                        <tr>
                            <td>7791234567890</td>
                            <td>18500.50</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<div id="summaryBox" class="d-none mb-4"></div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h5 class="mb-1">Productos a actualizar</h5>
            <small class="text-muted">
                Comparación entre el valor actual y el valor importado
            </small>
        </div>

        <div class="d-flex gap-2">
            <button
                type="button"
                id="resetButton"
                class="btn btn-outline-secondary d-none"
                onclick="resetImport()"
            >
                <i class="ri-refresh-line me-1"></i>
                Procesar de nuevo
            </button>

            <button
                type="button"
                id="applyButton"
                class="btn btn-success"
                onclick="applyImport()"
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
                    <th>Fila</th>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Campo</th>
                    <th class="text-end">Precio anterior</th>
                    <th class="text-end">Precio nuevo</th>
                    <th class="text-end">Diferencia</th>
                    <th class="text-end">Variación</th>
                </tr>
            </thead>

            <tbody id="previewRows">
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
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
            <nav aria-label="Paginación de productos">
                <ul class="pagination pagination-sm mb-0" id="previewPagination"></ul>
            </nav>
        </div>
    </div>
</div>

<div class="row">

    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-1">Filas ignoradas</h5>
                <small class="text-muted">
                    Códigos que no fueron encontrados en el sistema
                </small>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fila</th>
                            <th>Código</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>

                    <tbody id="ignoredRows">
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                Sin información
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-1">Errores del archivo</h5>
                <small class="text-muted">
                    Filas que no pudieron procesarse
                </small>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fila</th>
                            <th>Código</th>
                            <th>Error</th>
                        </tr>
                    </thead>

                    <tbody id="errorRows">
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                Sin información
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
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

    return `${Number(value).toFixed(2)}%`;
}

function priceFieldLabel(field) {
    const labels = {
        price_a: 'Precio A sin IVA',
        price_a_with_tax: 'Precio A con IVA',
        price_b: 'Precio B sin IVA',
        price_b_with_tax: 'Precio B con IVA',
        price_c: 'Precio C sin IVA',
        price_c_with_tax: 'Precio C con IVA',
        price_d: 'Precio D sin IVA',
        price_d_with_tax: 'Precio D con IVA'
    };

    return labels[field] ?? field;
}

function differenceBadge(value) {
    const amount = Number(value ?? 0);

    if (amount > 0) {
        return `
            <span class="badge bg-label-danger">
                +${money(amount)}
            </span>
        `;
    }

    if (amount < 0) {
        return `
            <span class="badge bg-label-success">
                ${money(amount)}
            </span>
        `;
    }

    return `
        <span class="badge bg-label-secondary">
            Sin cambios
        </span>
    `;
}

function renderSummary(summary) {
    document.getElementById('summaryBox').classList.remove('d-none');

    document.getElementById('summaryBox').innerHTML = `
        <div class="row">

            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-muted">Filas procesadas</small>
                        <h3 class="mb-0">${summary.total_rows ?? 0}</h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-muted">Productos encontrados</small>
                        <h3 class="mb-0 text-success">${summary.matched ?? 0}</h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-muted">Filas ignoradas</small>
                        <h3 class="mb-0 text-warning">${summary.ignored ?? 0}</h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <small class="text-muted">Errores</small>
                        <h3 class="mb-0 text-danger">${summary.errors ?? 0}</h3>
                    </div>
                </div>
            </div>

        </div>
    `;
}

function renderPreview(items) {
    const rows = document.getElementById('previewRows');
    const paginationBox = document.getElementById('previewPaginationBox');

    if (!items.length) {
        rows.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted py-5">
                    No se encontraron productos para actualizar.
                </td>
            </tr>
        `;

        paginationBox.classList.add('d-none');
        paginationBox.classList.remove('d-flex');
        return;
    }

    const pageSize = Number(document.getElementById('previewPageSize').value || 25);
    const totalPages = Math.max(1, Math.ceil(items.length / pageSize));
    previewPage = Math.min(Math.max(previewPage, 1), totalPages);
    const start = (previewPage - 1) * pageSize;

    rows.innerHTML = items.slice(start, start + pageSize).map(item => `
        <tr>
            <td>${item.row}</td>

            <td>
                <span class="badge bg-label-secondary">
                    ${item.code}
                </span>
            </td>

            <td>
                <strong>${item.product_name ?? '-'}</strong>
                <br>
                <small class="text-muted">
                    ID local: ${item.product_id}
                </small>
            </td>

            <td>
                ${priceFieldLabel(item.price_field)}
            </td>

            <td class="text-end">
                ${money(item.old_value)}
            </td>

            <td class="text-end">
                <strong>${money(item.new_value)}</strong>
            </td>

            <td class="text-end">
                ${differenceBadge(item.difference)}
            </td>

            <td class="text-end">
                ${percentage(item.difference_percentage)}
            </td>
        </tr>
    `).join('');

    document.getElementById('previewRange').textContent =
        `${start + 1}-${Math.min(start + pageSize, items.length)} de ${items.length}`;

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

function renderIgnored(items) {
    const rows = document.getElementById('ignoredRows');

    if (!items.length) {
        rows.innerHTML = `
            <tr>
                <td colspan="3" class="text-center text-muted py-4">
                    No hubo filas ignoradas.
                </td>
            </tr>
        `;

        return;
    }

    rows.innerHTML = items.map(item => `
        <tr>
            <td>${item.row ?? '-'}</td>
            <td>${item.code ?? '-'}</td>
            <td>${item.message ?? '-'}</td>
        </tr>
    `).join('');
}

function renderErrors(items) {
    const rows = document.getElementById('errorRows');

    if (!items.length) {
        rows.innerHTML = `
            <tr>
                <td colspan="3" class="text-center text-muted py-4">
                    No se detectaron errores.
                </td>
            </tr>
        `;

        return;
    }

    rows.innerHTML = items.map(item => `
        <tr>
            <td>${item.row ?? '-'}</td>
            <td>${item.code ?? '-'}</td>
            <td>
                <span class="text-danger">
                    ${item.message ?? '-'}
                </span>
            </td>
        </tr>
    `).join('');
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
            Simular
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

document
    .getElementById('importForm')
    .addEventListener('submit', async event => {
        event.preventDefault();

        const form = new FormData(event.target);
        const file = form.get('file');

        if (!(file instanceof File) || file.size === 0) {
            alert('Seleccioná un archivo para importar.');
            return;
        }

        simulationToken = null;
        simulationData = null;

        document.getElementById('applyButton').disabled = true;
        document.getElementById('resetButton').classList.add('d-none');

        setSimulatingState(true);

        try {
            const response = await fetch(`${window.APP_BASE_URL}/api/product-price-import/simulate`,
                {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json'
                    },
                    body: form
                }
            );

            const responseText = await response.text();
            let data = {};
            try {
                data = responseText ? JSON.parse(responseText) : {};
            } catch (error) {
                alert(`El servidor interrumpió la carga inicial (HTTP ${response.status}).`);
                return;
            }

            if (!response.ok) {
                const errors = data.errors
                    ? Object.values(data.errors).flat().join('\n')
                    : '';

                alert([
                    data.message ?? 'No se pudo simular la importación.',
                    errors
                ].filter(Boolean).join('\n'));

                return;
            }

            simulationToken = data.token;
            const progressBox = document.getElementById('progressBox');
            progressBox.classList.remove('d-none');
            document.getElementById('progressTitle').textContent = 'Analizando precios por bloques...';
            document.getElementById('progressBar').style.width = '0%';
            document.getElementById('progressLabel').textContent = `0% (0/${data.total ?? 0})`;

            while (!data.done) {
                const analysisResponse = await fetch(`${window.APP_BASE_URL}/api/product-price-import/analyze`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', Accept: 'application/json'},
                    body: JSON.stringify({token: simulationToken})
                });
                const analysisText = await analysisResponse.text();

                try {
                    data = analysisText ? JSON.parse(analysisText) : {};
                } catch (error) {
                    alert(`El servidor interrumpió un bloque de análisis (HTTP ${analysisResponse.status}). Intentá nuevamente.`);
                    return;
                }

                if (!analysisResponse.ok) {
                    alert(data.message ?? 'No se pudo continuar el análisis del archivo.');
                    return;
                }

                const progress = Number(data.progress ?? 0);
                document.getElementById('progressBar').style.width = `${progress}%`;
                document.getElementById('progressLabel').textContent =
                    `${progress}% (${data.processed ?? 0}/${data.total ?? 0})`;
            }

            simulationData = data;
            previewPage = 1;
            document.getElementById('progressTitle').textContent = 'Análisis completado';

            renderSummary(data.summary ?? {});
            renderPreview(data.preview ?? []);
            renderIgnored(data.ignored ?? []);
            renderErrors(data.errors ?? []);

            document.getElementById('resetButton').classList.remove('d-none');

            document.getElementById('applyButton').disabled =
                !(data.preview ?? []).length;

        } catch (error) {
            console.error(error);
            alert('No se pudo conectar con el servidor.');
        } finally {
            setSimulatingState(false);
        }
    });

async function applyImport() {
    if (!simulationToken) {
        alert('Primero debés simular una importación.');
        return;
    }

    const total = simulationData?.preview?.length ?? 0;

    const confirmed = await window.erpConfirm(
        `Se actualizarán ${total} producto(s).\n\n` +
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

    document.getElementById('progressBox').classList.remove('d-none');
    document.getElementById('progressTitle').textContent = 'Actualizando precios por bloques...';
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressLabel').textContent = '0%';

    while (true) {
        let response;
        try {
            response = await fetch(`${window.APP_BASE_URL}/api/product-price-import/apply`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify({
                    token: simulationToken
                })
            });
        } catch (error) {
            button.disabled = false;
            button.innerHTML = '<i class="ri-save-line me-1"></i>Continuar guardando';
            alert('Se perdió la conexión. El progreso confirmado se conserva; presioná Continuar guardando para retomar.');
            return;
        }

        const responseText = await response.text();
        let data = {};
        try {
            data = responseText ? JSON.parse(responseText) : {};
        } catch (error) {
            data = {};
        }

        if (!response.ok) {
            button.disabled = false;
            button.innerHTML = '<i class="ri-save-line me-1"></i>Continuar guardando';
            const timeoutMessage = [502, 503, 504].includes(response.status)
                ? 'El servidor demoró demasiado en confirmar este bloque.'
                : (data.message ?? `No se pudo guardar el bloque (HTTP ${response.status}).`);
            alert(`${timeoutMessage}\n\nEl progreso anterior se conserva. Presioná Continuar guardando para retomar.`);
            return;
        }

        const progress = Number(data.progress ?? 0);
        document.getElementById('progressBar').style.width = `${progress}%`;
        document.getElementById('progressLabel').textContent =
            `${progress}% (${data.processed ?? 0}/${data.total ?? 0})`;

        if (!data.done) {
            continue;
        }

        alert(`${data.message}\nProductos actualizados: ${data.updated ?? 0}`);
        simulationToken = null;
        button.disabled = true;
        button.innerHTML = `
            <i class="ri-check-line me-1"></i>
            Cambios aplicados
        `;
        document.getElementById('progressTitle').textContent = 'Actualización completada';
        return;
    }
}

function resetImport() {
    simulationToken = null;
    simulationData = null;
    previewPage = 1;

    document.getElementById('importForm').reset();

    document.getElementById('summaryBox').classList.add('d-none');
    document.getElementById('summaryBox').innerHTML = '';
    document.getElementById('progressBox').classList.add('d-none');
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressLabel').textContent = '0%';

    document.getElementById('previewRows').innerHTML = `
        <tr>
            <td colspan="8" class="text-center text-muted py-5">
                Todavía no se realizó ninguna simulación.
            </td>
        </tr>
    `;

    document.getElementById('previewPaginationBox').classList.add('d-none');
    document.getElementById('previewPaginationBox').classList.remove('d-flex');

    document.getElementById('ignoredRows').innerHTML = `
        <tr>
            <td colspan="3" class="text-center text-muted py-4">
                Sin información
            </td>
        </tr>
    `;

    document.getElementById('errorRows').innerHTML = `
        <tr>
            <td colspan="3" class="text-center text-muted py-4">
                Sin información
            </td>
        </tr>
    `;

    document.getElementById('applyButton').disabled = true;
    document.getElementById('applyButton').innerHTML = `
        <i class="ri-save-line me-1"></i>
        Guardar cambios
    `;

    document.getElementById('resetButton').classList.add('d-none');
}
</script>

@endsection
