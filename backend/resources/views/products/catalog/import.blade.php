@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Importación masiva de productos</h4>
        <small class="text-muted">Crea o actualiza productos, variantes, maestros y stock por depósito.</small>
    </div>
    <a href="{{ url('/demo/products') }}" class="btn btn-secondary"><i class="ri-arrow-left-line me-1"></i>Volver</a>
</div>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-1">Archivo y configuración</h5></div>
    <div class="card-body">
        <form id="catalogImportForm" enctype="multipart/form-data">
            <div class="row align-items-end">
                <div class="col-lg-5 mb-3">
                    <label class="form-label">Excel o CSV *</label>
                    <input class="form-control" type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
                    <small class="text-muted">Máximo 20 MB. Los códigos se conservan como texto.</small>
                </div>
                <div class="col-lg-3 mb-3">
                    <label class="form-label">Los costos y precios del archivo son *</label>
                    <select class="form-select" name="price_mode" required>
                        <option value="gross">Finales con IVA</option>
                        <option value="net">Netos sin IVA</option>
                    </select>
                    <small class="text-muted">El otro valor se calcula usando la columna ALÍCUOTA. Si está vacía, se usa 0%.</small>
                </div>
                <div class="col-lg-2 mb-3">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="create_missing_masters" value="1" id="createMasters" checked>
                        <label class="form-check-label" for="createMasters">Crear maestros faltantes</label>
                    </div>
                </div>
                <div class="col-lg-2 mb-3">
                    <button class="btn btn-primary w-100" id="simulateButton" type="submit">
                        <i class="ri-search-eye-line me-1"></i>Simular
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="results" class="d-none">
    <div id="summary" class="row g-3 mb-4"></div>

    <div id="stockLocations" class="mb-4"></div>
    <div id="messages" class="row g-3 mb-4"></div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-1">Productos detectados</h5>
                <small class="text-muted" id="previewNote"></small>
            </div>
            <div class="d-flex flex-wrap align-items-center justify-content-end gap-3">
                <div class="form-check d-none" id="skipErrorsBox">
                    <input class="form-check-input" type="checkbox" id="skipErrors">
                    <label class="form-check-label" for="skipErrors">Omitir productos con errores</label>
                </div>
                <button class="btn btn-success" id="applyButton" type="button" disabled>
                    <i class="ri-upload-cloud-line me-1"></i>Importar catálogo
                </button>
            </div>
        </div>
        <div id="progressBox" class="px-4 pt-3 d-none">
            <div class="d-flex justify-content-between mb-1"><span id="progressTitle">Procesando por bloques...</span><span id="progressLabel">0%</span></div>
            <div class="progress"><div id="progressBar" class="progress-bar" style="width:0%"></div></div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Fila</th><th>ID externo</th><th>Producto</th><th>Código principal</th><th>Acción</th><th class="text-end">Variantes</th></tr></thead>
                <tbody id="previewRows"></tbody>
            </table>
        </div>
        <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-3" id="previewPaginationBox">
            <div class="d-flex align-items-center gap-2">
                <label for="previewPageSize" class="text-muted mb-0">Mostrar</label>
                <select id="previewPageSize" class="form-select form-select-sm" style="width:auto">
                    <option value="10">10</option><option value="25" selected>25</option>
                    <option value="50">50</option><option value="100">100</option>
                </select>
                <span class="text-muted">por página</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <small class="text-muted" id="previewRange"></small>
                <nav aria-label="Paginación de productos"><ul class="pagination pagination-sm mb-0" id="previewPagination"></ul></nav>
            </div>
        </div>
    </div>
</div>

<script>
let catalogToken = null;
let catalogSimulation = null;
let previewPage = 1;

const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
})[char]);

function statCard(label, value, color = '') {
    return `<div class="col-6 col-lg-2"><div class="card h-100"><div class="card-body">
        <small class="text-muted">${label}</small><h3 class="mb-0 ${color}">${value ?? 0}</h3>
    </div></div></div>`;
}

function renderPreviewPage() {
    const preview = catalogSimulation?.preview ?? [];
    const pageSize = Number(document.getElementById('previewPageSize').value || 25);
    const totalPages = Math.max(1, Math.ceil(preview.length / pageSize));
    previewPage = Math.min(Math.max(previewPage, 1), totalPages);
    const start = (previewPage - 1) * pageSize;

    document.getElementById('previewRows').innerHTML = preview.slice(start, start + pageSize).map(item => `
        <tr><td>${item.row}</td><td>${escapeHtml(item.external_id || '-')}</td>
        <td><strong>${escapeHtml(item.name)}</strong><br><small class="text-muted">Ref.: ${escapeHtml(item.reference_code || '-')}</small></td>
        <td>${escapeHtml(item.main_barcode || '-')}</td>
        <td><span class="badge ${item.has_errors ? 'bg-label-danger' : (item.action === 'create' ? 'bg-label-success' : 'bg-label-primary')}">${item.has_errors ? 'Excluir' : (item.action === 'create' ? 'Crear' : 'Comparar')}</span></td>
        <td class="text-end"><strong>${item.variants}</strong><br><small class="text-muted">${item.variant_creates} nuevas · ${item.variant_updates} actualizadas</small></td></tr>
    `).join('') || '<tr><td colspan="6" class="text-center text-muted py-5">Sin productos válidos.</td></tr>';

    document.getElementById('previewRange').textContent = preview.length
        ? `${start + 1}-${Math.min(start + pageSize, preview.length)} de ${preview.length}`
        : '0 productos';

    const pages = [];
    for (let page = Math.max(1, previewPage - 2); page <= Math.min(totalPages, previewPage + 2); page++) pages.push(page);
    document.getElementById('previewPagination').innerHTML = `
        <li class="page-item ${previewPage === 1 ? 'disabled' : ''}"><button class="page-link" type="button" data-preview-page="${previewPage - 1}" aria-label="Anterior">&lsaquo;</button></li>
        ${pages.map(page => `<li class="page-item ${page === previewPage ? 'active' : ''}"><button class="page-link" type="button" data-preview-page="${page}">${page}</button></li>`).join('')}
        <li class="page-item ${previewPage === totalPages ? 'disabled' : ''}"><button class="page-link" type="button" data-preview-page="${previewPage + 1}" aria-label="Siguiente">&rsaquo;</button></li>`;
    document.getElementById('previewPaginationBox').classList.toggle('d-none', preview.length === 0);
}

function renderSimulation(data) {
    document.getElementById('results').classList.remove('d-none');
    const summary = data.summary ?? {};
    document.getElementById('summary').innerHTML = [
        statCard('Filas', summary.total_rows), statCard('Productos', summary.products),
        statCard('Variantes', summary.variants), statCard('Productos nuevos', summary.new_products, 'text-success'),
        statCard('Productos existentes', summary.updated_products, 'text-primary'), statCard('Errores', summary.errors, summary.errors ? 'text-danger' : 'text-success')
    ].join('');

    const locations = data.stock_locations ?? [];
    document.getElementById('stockLocations').innerHTML = locations.length
        ? `<div class="card"><div class="card-body"><strong>Stock detectado</strong><div class="d-flex flex-wrap gap-2 mt-2">${locations.map(location =>
            `<span class="badge bg-label-primary">${escapeHtml(location.branch_name)} · ${escapeHtml(location.warehouse_name)}</span>`
        ).join('')}</div></div></div>`
        : `<div class="alert alert-warning mb-0">No se detectaron columnas de stock con el formato STOCK - DEPOS: ... - SUR: ...</div>`;

    const errors = data.errors ?? [];
    const warnings = data.warnings ?? [];
    document.getElementById('messages').innerHTML = `
        <div class="col-lg-6"><div class="card h-100"><div class="card-header"><strong>Errores (${summary.errors ?? errors.length})</strong></div>
            <div class="card-body ${errors.length ? 'text-danger' : 'text-success'}">${errors.length
                ? `${(summary.errors ?? errors.length) > errors.length ? `<small class="d-block mb-2">Se muestran los primeros ${errors.length} errores.</small>` : ''}<ul class="mb-0">${errors.slice(0, 100).map(item => `<li><strong>Fila ${item.row ?? '-'}</strong>${item.field ? ` — ${escapeHtml(item.field)}` : ''}: ${escapeHtml(item.message)}</li>`).join('')}</ul>`
                : 'No se detectaron errores.'}</div></div></div>
        <div class="col-lg-6"><div class="card h-100"><div class="card-header"><strong>Advertencias (${warnings.length})</strong></div>
            <div class="card-body">${warnings.length
                ? `<ul class="mb-0">${warnings.map(item => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`
                : '<span class="text-success">Sin advertencias.</span>'}</div></div></div>`;

    previewPage = 1;
    renderPreviewPage();

    document.getElementById('previewNote').textContent = summary.preview_limited
        ? 'Se muestran los primeros 250 productos.' : 'Vista previa completa.';
    const skipErrorsBox = document.getElementById('skipErrorsBox');
    const skipErrors = document.getElementById('skipErrors');
    skipErrors.checked = false;
    skipErrorsBox.classList.toggle('d-none', errors.length === 0);
    document.getElementById('applyButton').disabled = !(summary.valid_products > 0) || errors.length > 0;
}

document.getElementById('skipErrors').addEventListener('change', event => {
    const summary = catalogSimulation?.summary ?? {};
    document.getElementById('applyButton').disabled = !(summary.valid_products > 0) || !event.target.checked;
});

document.getElementById('previewPageSize').addEventListener('change', () => {
    previewPage = 1;
    renderPreviewPage();
});

document.getElementById('previewPagination').addEventListener('click', event => {
    const button = event.target.closest('[data-preview-page]');
    if (!button || button.closest('.page-item').classList.contains('disabled')) return;
    previewPage = Number(button.dataset.previewPage);
    renderPreviewPage();
    document.getElementById('previewRows').closest('.card').scrollIntoView({behavior: 'smooth', block: 'start'});
});

document.getElementById('catalogImportForm').addEventListener('submit', async event => {
    event.preventDefault();
    catalogToken = null;
    const button = document.getElementById('simulateButton');
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Analizando...';
    try {
        const response = await fetch(`${window.APP_BASE_URL}/api/product-catalog-import/simulate`, {
            method: 'POST', headers: {Accept: 'application/json'}, body: new FormData(event.target)
        });
        const responseText = await response.text();
        let data = {};
        try {
            data = responseText ? JSON.parse(responseText) : {};
        } catch (error) {
            if (!response.ok) {
                alert(`El servidor interrumpió el análisis (HTTP ${response.status}). El archivo puede necesitar más tiempo de procesamiento.`);
                return;
            }
            throw error;
        }
        if (!response.ok) {
            const validation = data.errors ? Object.values(data.errors).flat().join('\n') : '';
            alert([data.message ?? 'No se pudo analizar el archivo.', validation].filter(Boolean).join('\n'));
            return;
        }
        catalogToken = data.token;
        document.getElementById('progressBox').classList.remove('d-none');
        document.getElementById('progressTitle').textContent = 'Analizando el archivo por bloques...';
        document.getElementById('progressBar').style.width = '0%';
        document.getElementById('progressLabel').textContent = `0% (0/${data.total ?? 0})`;

        while (!data.done) {
            const analysisResponse = await fetch(`${window.APP_BASE_URL}/api/product-catalog-import/analyze`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', Accept: 'application/json'},
                body: JSON.stringify({token: catalogToken})
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
            document.getElementById('progressLabel').textContent = `${progress}% (${data.processed ?? data.total ?? 0}/${data.total ?? data.processed ?? 0})`;
        }

        catalogSimulation = data;
        document.getElementById('progressTitle').textContent = 'Análisis completado';
        renderSimulation(data);
    } catch (error) {
        console.error(error); alert('No se pudo conectar con el servidor.');
    } finally {
        button.disabled = false;
        button.innerHTML = '<i class="ri-search-eye-line me-1"></i>Simular';
    }
});

document.getElementById('applyButton').addEventListener('click', async () => {
    if (!catalogToken) return;
    const summary = catalogSimulation?.summary ?? {};
    const confirmed = await window.erpConfirm(
        `${document.getElementById('skipErrors').checked ? `Se omitirán ${summary.invalid_products ?? 0} productos con errores.\n` : ''}Se importarán ${summary.valid_products ?? summary.products ?? 0} productos válidos.\n\n¿Confirmás la importación?`
    );
    if (!confirmed) return;

    const button = document.getElementById('applyButton');
    button.disabled = true;
    document.getElementById('progressBox').classList.remove('d-none');
    document.getElementById('progressTitle').textContent = 'Importando por bloques...';
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressLabel').textContent = '0%';

    while (true) {
        let response;
        try {
            response = await fetch(`${window.APP_BASE_URL}/api/product-catalog-import/apply`, {
                method: 'POST', headers: {'Content-Type': 'application/json', Accept: 'application/json'},
                body: JSON.stringify({token: catalogToken, skip_errors: document.getElementById('skipErrors').checked})
            });
        } catch (error) {
            button.disabled = false;
            alert('Se perdió la conexión. Podés presionar Importar catálogo para continuar desde el último bloque confirmado.');
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
            if ([502, 503, 504].includes(response.status)) {
                alert(`El servidor demoró demasiado en confirmar este bloque (HTTP ${response.status}).\n\nEl progreso anterior se conserva. Esperá unos segundos y presioná nuevamente “Importar catálogo” para continuar desde el último bloque confirmado.`);
                return;
            }
            alert(data.message ?? `No se pudo aplicar la importación (HTTP ${response.status}). El progreso anterior se conserva y podés volver a intentarlo.`);
            return;
        }

        const progress = Number(data.progress ?? 0);
        document.getElementById('progressBar').style.width = `${progress}%`;
        document.getElementById('progressLabel').textContent = `${progress}% (${data.processed}/${data.total})`;

        if (data.done) {
            catalogToken = null;
            button.innerHTML = '<i class="ri-check-line me-1"></i>Importación completada';
            const totals = data.totals ?? {};
            alert(`Catálogo importado correctamente.\nProductos creados: ${totals.created_products ?? 0}\nProductos actualizados: ${totals.updated_products ?? 0}\nProductos sin cambios: ${totals.skipped_products ?? 0}\nVariantes creadas: ${totals.created_variants ?? 0}\nVariantes actualizadas: ${totals.updated_variants ?? 0}\nVariantes sin cambios: ${totals.skipped_variants ?? 0}`);
            return;
        }
    }
});
</script>
@endsection
