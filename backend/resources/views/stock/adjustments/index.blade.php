{{-- Creación multiítem y listado de ajustes de stock. --}}
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1">Ajuste de Stock</h3>
        <small class="text-muted">Registrá el conteo real de uno o varios productos</small>
    </div>
</div>

<div class="alert alert-danger d-none" id="pageLoadError"></div>

<form id="adjustmentForm">
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Cabecera</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Sucursal</label>
                    <select class="form-select" id="branch_name" required></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Depósito</label>
                    <select class="form-select" id="warehouse_name" required></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha</label>
                    <input class="form-control" id="adjustment_date" type="datetime-local" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Motivo</label>
                    <select class="form-select" id="reason_id">
                        <option value="">Sin motivo</option>
                    </select>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Observaciones</label>
                    <input class="form-control" id="notes" maxlength="2000">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="adjust_by_variant">
                        <label class="form-check-label" for="adjust_by_variant">Ajuste por variante</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Productos</h5>
                <small class="text-muted">La cantidad es el stock real contado, no la diferencia</small>
            </div>
            <button class="btn btn-primary rounded-circle" type="button" id="addItemButton" title="Agregar producto" style="width: 38px; height: 38px;">+</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead id="itemsHead"></thead>
                <tbody id="itemsRows"></tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-4">
        <button class="btn btn-success" id="saveButton" type="submit">Guardar ajuste</button>
    </div>
</form>

<div class="card" id="adjustmentsHistoryCard">
    <div class="card-header"><h5 class="mb-0">Últimos ajustes</h5></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Sucursal</th>
                    <th>Depósito</th>
                    <th>Cantidad ajustada</th>
                    <th>Motivo</th>
                    <th>Fecha</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="adjustmentRows"></tbody>
        </table>
    </div>
    @include('components.api-pagination')
</div>

<div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="itemForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="itemModalTitle">Agregar producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Producto</label>
                            <select
                                class="form-select"
                                id="item_product_id"
                                data-remote-url="{{ url('/api/products') }}"
                                data-remote-placeholder="Buscar por nombre, código o código de barras..."
                                required
                            ><option value=""></option></select>
                        </div>
                        <div class="col-md-6 variant-field d-none">
                            <label class="form-label">Color</label>
                            <select class="form-select" id="item_color"></select>
                        </div>
                        <div class="col-md-6 variant-field d-none">
                            <label class="form-label">Talle</label>
                            <select class="form-select" id="item_size"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stock actual en el depósito</label>
                            <input class="form-control" id="item_current_stock" readonly value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cantidad contada</label>
                            <input class="form-control" id="item_quantity" type="number" min="0" step="0.0001" required>
                        </div>
                    </div>
                    <div class="alert alert-danger d-none mt-3 mb-0" id="itemError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Aceptar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
let locations = [];
let adjustmentItems = [];
let editingIndex = null;
let selectedProduct = null;
let productVariants = [];
let itemModalInstance = null;

function modalInstance() {
    if (!window.bootstrap?.Modal) {
        throw new Error('No se pudo cargar el componente de ventanas del sistema.');
    }

    itemModalInstance ??= window.bootstrap.Modal.getOrCreateInstance(document.getElementById('itemModal'));
    return itemModalInstance;
}

const escapeHtml = value => String(value ?? '')
    .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;').replaceAll("'", '&#039;');

const formatQuantity = value => Number(value ?? 0).toLocaleString('es-AR', { maximumFractionDigits: 4 });

function setDefaultDate() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    adjustment_date.value = now.toISOString().slice(0, 16);
}

async function loadHeaderOptions() {
    const [locationResponse, reasonResponse] = await Promise.all([
        fetch(`${window.APP_BASE_URL}/api/stock-adjustments-options`),
        fetch(`${window.APP_BASE_URL}/api/stock-adjustment-reasons`)
    ]);
    if (!locationResponse.ok) {
        throw new Error(`No se pudieron cargar sucursales y depósitos (HTTP ${locationResponse.status}).`);
    }
    if (!reasonResponse.ok) {
        throw new Error(`No se pudieron cargar los motivos (HTTP ${reasonResponse.status}).`);
    }

    locations = (await locationResponse.json()).locations ?? [];
    if (!locations.length) {
        locations = [{
            branch_name: 'SUCURSAL',
            warehouse_name: 'DEPÓSITO PRINCIPAL'
        }];
    }
    const reasonsData = await reasonResponse.json();

    const branches = [...new Set(locations.map(location => location.branch_name))];
    branch_name.innerHTML = branches.map(branch => `<option value="${escapeHtml(branch)}">${escapeHtml(branch)}</option>`).join('');
    loadWarehouses();

    (reasonsData.data ?? reasonsData).forEach(reason => {
        reason_id.insertAdjacentHTML('beforeend', `<option value="${reason.id}">${escapeHtml(reason.name)}</option>`);
    });
}

function loadWarehouses() {
    const current = warehouse_name.value;
    const warehouses = [...new Set(
        locations
            .filter(location => location.branch_name === branch_name.value)
            .map(location => location.warehouse_name)
    )];
    warehouse_name.innerHTML = warehouses.map(warehouse => `<option value="${escapeHtml(warehouse)}">${escapeHtml(warehouse)}</option>`).join('');
    if (warehouses.includes(current)) warehouse_name.value = current;
    refreshItemStocks();
}

async function stockFor(productId, variantId = null) {
    const params = new URLSearchParams({
        product_id: productId,
        branch_name: branch_name.value,
        warehouse_name: warehouse_name.value
    });
    if (variantId) params.set('product_variant_id', variantId);

    const response = await fetch(`${window.APP_BASE_URL}/api/stock-adjustments-current-stock?${params}`);
    if (!response.ok) throw new Error('No se pudo consultar el stock del depósito.');
    return Number((await response.json()).current_stock ?? 0);
}

async function refreshItemStocks() {
    if (!branch_name.value || !warehouse_name.value) return;
    await Promise.all(adjustmentItems.map(async item => {
        item.current_stock = await stockFor(item.product_id, item.product_variant_id);
    }));
    renderItems();
}

function renderItems() {
    const byVariant = adjust_by_variant.checked;
    itemsHead.innerHTML = `<tr>
        <th>Código</th><th>Producto</th>
        ${byVariant ? '<th>Talle</th><th>Color</th>' : ''}
        <th class="text-end">Stock actual</th>
        <th class="text-end">Cantidad</th><th class="text-end">Acciones</th>
    </tr>`;

    itemsRows.innerHTML = adjustmentItems.length ? adjustmentItems.map((item, index) => `<tr>
        <td>${escapeHtml(item.code || '-')}</td>
        <td><strong>${escapeHtml(item.product_name)}</strong></td>
        ${byVariant ? `
            <td>${escapeHtml(item.size_name || 'Sin talle')}</td>
            <td>${escapeHtml(item.color_name || 'Sin color')}</td>
        ` : ''}
        <td class="text-end">${formatQuantity(item.current_stock)}</td>
        <td class="text-end fw-semibold">${formatQuantity(item.quantity)}</td>
        <td class="text-end">
            <button class="btn btn-sm btn-warning" type="button" onclick="editItem(${index})">Editar</button>
            <button class="btn btn-sm btn-outline-danger" type="button" onclick="removeItem(${index})">Eliminar</button>
        </td>
    </tr>`).join('') : `<tr><td colspan="${byVariant ? 7 : 5}" class="text-center py-4 text-muted">Todavía no agregaste productos.</td></tr>`;
}

function resetItemForm() {
    editingIndex = null;
    selectedProduct = null;
    productVariants = [];
    itemForm.reset();
    item_product_id._remoteSelect?.clear();
    item_color.innerHTML = '';
    item_size.innerHTML = '';
    item_current_stock.value = '0';
    itemError.classList.add('d-none');
    itemModalTitle.textContent = 'Agregar producto';
    document.querySelectorAll('.variant-field').forEach(field => field.classList.toggle('d-none', !adjust_by_variant.checked));
}

function openNewItem() {
    resetItemForm();
    modalInstance().show();
}

async function loadProduct(productId) {
    if (!productId) {
        selectedProduct = null;
        productVariants = [];
        return;
    }
    const response = await fetch(`${window.APP_BASE_URL}/api/products/${productId}`);
    if (!response.ok) throw new Error('No se pudo cargar el producto.');
    selectedProduct = await response.json();
    productVariants = selectedProduct.variants ?? [];

    if (adjust_by_variant.checked) {
        if (!productVariants.length) throw new Error('El producto no tiene variantes registradas.');
        loadColors();
    } else if (productVariants.length > 1) {
        throw new Error('El producto posee varias variantes. Activá "Ajuste por variante" para ajustarlo.');
    }
}

const variantValue = value => value === null || value === undefined ? '__none' : String(value);

function loadColors(selected = null) {
    const colors = new Map();
    productVariants.forEach(variant => colors.set(
        variantValue(variant.color_id),
        variant.color?.name ?? 'Sin color'
    ));
    item_color.innerHTML = [...colors].map(([id, name]) => `<option value="${id}">${escapeHtml(name)}</option>`).join('');
    if (selected !== null) item_color.value = variantValue(selected);
    loadSizes();
}

function loadSizes(selected = null) {
    const sizes = new Map();
    productVariants
        .filter(variant => variantValue(variant.color_id) === item_color.value)
        .forEach(variant => sizes.set(variantValue(variant.size_id), variant.size?.name ?? 'Sin talle'));
    item_size.innerHTML = [...sizes].map(([id, name]) => `<option value="${id}">${escapeHtml(name)}</option>`).join('');
    if (selected !== null) item_size.value = variantValue(selected);
    updateCurrentStock();
}

function selectedVariant() {
    return productVariants.find(variant =>
        variantValue(variant.color_id) === item_color.value &&
        variantValue(variant.size_id) === item_size.value
    ) ?? null;
}

async function updateCurrentStock() {
    if (!selectedProduct) return;
    const variant = adjust_by_variant.checked ? selectedVariant() : null;
    item_current_stock.value = adjust_by_variant.checked && !variant
        ? '0'
        : formatQuantity(await stockFor(selectedProduct.id, variant?.id));
}

async function editItem(index) {
    resetItemForm();
    editingIndex = index;
    const item = adjustmentItems[index];
    itemModalTitle.textContent = 'Editar producto';
    item_product_id.innerHTML = `<option value="${item.product_id}" selected>${escapeHtml(item.product_name)}</option>`;
    if (item_product_id._remoteSelect) item_product_id._remoteSelect.input.value = item.product_name;
    item_quantity.value = item.quantity;
    modalInstance().show();

    try {
        await loadProduct(item.product_id);
        if (adjust_by_variant.checked) {
            loadColors(item.color_id);
            loadSizes(item.size_id);
            await updateCurrentStock();
        }
    } catch (error) {
        showItemError(error.message);
    }
}

function removeItem(index) {
    adjustmentItems.splice(index, 1);
    renderItems();
}

function showItemError(message) {
    itemError.textContent = message;
    itemError.classList.remove('d-none');
}

item_product_id.addEventListener('change', async () => {
    try {
        itemError.classList.add('d-none');
        await loadProduct(item_product_id.value);
        await updateCurrentStock();
    } catch (error) {
        showItemError(error.message);
    }
});
item_color.addEventListener('change', () => loadSizes());
item_size.addEventListener('change', updateCurrentStock);

itemForm.addEventListener('submit', async event => {
    event.preventDefault();
    try {
        if (!selectedProduct) throw new Error('Seleccioná un producto.');
        if (item_quantity.value === '') throw new Error('Ingresá la cantidad contada.');

        const variant = adjust_by_variant.checked ? selectedVariant() : null;
        if (adjust_by_variant.checked && !variant) throw new Error('Seleccioná una variante existente.');

        const key = `${selectedProduct.id}:${variant?.id ?? 'product'}`;
        const duplicate = adjustmentItems.some((item, index) =>
            index !== editingIndex && `${item.product_id}:${item.product_variant_id ?? 'product'}` === key
        );
        if (duplicate) throw new Error('Ese producto y variante ya están incluidos en la grilla.');

        const item = {
            product_id: selectedProduct.id,
            product_variant_id: variant?.id ?? null,
            code: variant?.sku || selectedProduct.code || '',
            product_name: selectedProduct.name,
            color_id: variant?.color_id ?? null,
            color_name: variant?.color?.name ?? null,
            size_id: variant?.size_id ?? null,
            size_name: variant?.size?.name ?? null,
            current_stock: await stockFor(selectedProduct.id, variant?.id),
            quantity: Number(item_quantity.value)
        };

        if (editingIndex === null) adjustmentItems.push(item);
        else adjustmentItems[editingIndex] = item;

        renderItems();
        modalInstance().hide();
    } catch (error) {
        showItemError(error.message);
    }
});

adjust_by_variant.addEventListener('change', event => {
    if (adjustmentItems.length && !confirm('Cambiar el tipo de ajuste eliminará los productos agregados. ¿Continuar?')) {
        event.target.checked = !event.target.checked;
        return;
    }
    adjustmentItems = [];
    renderItems();
});
branch_name.addEventListener('change', loadWarehouses);
warehouse_name.addEventListener('change', refreshItemStocks);
addItemButton.addEventListener('click', openNewItem);

adjustmentForm.addEventListener('submit', async event => {
    event.preventDefault();
    if (!adjustmentItems.length) {
        alert('Agregá al menos un producto al ajuste.');
        return;
    }

    saveButton.disabled = true;
    try {
        const response = await fetch(`${window.APP_BASE_URL}/api/stock-adjustments`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({
                branch_name: branch_name.value,
                warehouse_name: warehouse_name.value,
                date: adjustment_date.value,
                reason_id: reason_id.value ? Number(reason_id.value) : null,
                adjust_by_variant: adjust_by_variant.checked,
                notes: notes.value || null,
                items: adjustmentItems.map(item => ({
                    product_id: item.product_id,
                    product_variant_id: item.product_variant_id,
                    quantity: item.quantity
                }))
            })
        });
        const data = await response.json();
        if (!response.ok) {
            const validation = Object.values(data.errors ?? {}).flat().join('\n');
            throw new Error(validation || data.message || 'No se pudo guardar el ajuste.');
        }
        location.href = `${window.APP_BASE_URL}/demo/stock/adjustments/${data.adjustment.id}`;
    } catch (error) {
        alert(error.message);
        saveButton.disabled = false;
    }
});

async function loadAdjustments(page = 1) {
    const params = new URLSearchParams({ page });
    const response = await fetch(`${window.APP_BASE_URL}/api/stock-adjustments?${params}`);
    if (!response.ok) {
        throw new Error(`No se pudieron cargar los últimos ajustes (HTTP ${response.status}).`);
    }
    const data = await response.json();
    const adjustments = data.data ?? data;
    adjustmentRows.innerHTML = adjustments.length ? adjustments.map(adjustment => `<tr>
        <td><a href="${window.APP_BASE_URL}/demo/stock/adjustments/${adjustment.id}"><strong>${escapeHtml(adjustment.number ?? ('#' + adjustment.id))}</strong></a></td>
        <td>${escapeHtml(adjustment.branch_name ?? '-')}</td>
        <td>${escapeHtml(adjustment.warehouse_name ?? '-')}</td>
        <td>${formatQuantity(adjustment.quantity)}</td>
        <td>${escapeHtml(adjustment.reason?.name ?? '-')}</td>
        <td>${escapeHtml(adjustment.date ?? '-')}</td>
        <td class="text-end"><a href="${window.APP_BASE_URL}/demo/stock/adjustments/${adjustment.id}" class="btn btn-sm btn-primary">Ver</a></td>
    </tr>`).join('') : '<tr><td colspan="7" class="text-center py-4 text-muted">No se encontraron ajustes de stock.</td></tr>';

    if (data.current_page) {
        const paginationRoot = document.querySelector('#adjustmentsHistoryCard [data-api-pagination]');
        renderApiPagination(data, loadAdjustments, paginationRoot);
    }
}

async function initializePage() {
    setDefaultDate();
    renderItems();
    erpEnhanceRemoteSelect(item_product_id);

    const results = await Promise.allSettled([
        loadHeaderOptions(),
        loadAdjustments()
    ]);
    const failures = results.filter(result => result.status === 'rejected');

    if (failures.length) {
        pageLoadError.textContent = failures
            .map(result => result.reason?.message ?? 'Error al cargar la pantalla.')
            .join(' ');
        pageLoadError.classList.remove('d-none');
    }
}

initializePage().catch(error => {
    pageLoadError.textContent = error.message ?? 'No se pudo inicializar la pantalla.';
    pageLoadError.classList.remove('d-none');
});
</script>
@endsection
