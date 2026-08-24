{{-- Vista: Nuevo registro de Facturas de Venta. Muestra el formulario para crear un registro de Facturas de Venta. --}}
@extends('layouts.app')

@section('content')

<style>
    .input-group>.erp-remote-select {
        flex: 1 1 auto;
        min-width: 0;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Nueva Factura</h4>
        <small class="text-muted">Carga de venta con productos por variante</small>
    </div>

    <a href="{{ url('/demo/sales') }}" class="btn btn-secondary">
        Volver
    </a>
</div>

<form id="saleForm">

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Encabezado</h5>
        </div>

        <div class="card-body">
            <div class="row">

                <div class="col-md-4 mb-3">
                    <label>Cliente</label>
                    <div class="input-group">
                        <select class="form-select" name="client_id" id="client_id" required data-remote-url="{{ url('/api/clients') }}" data-remote-placeholder="Buscar cliente por nombre, código o documento...">
                            <option value="">Cargando...</option>
                        </select>
                        <a class="btn btn-outline-primary" href="{{ url('/demo/clients/create') }}?return_to={{ urlencode(url('/demo/sales/create')) }}" title="Crear cliente">+</a>
                    </div>
                </div>

                <div class="col-md-2 mb-3">
                    <label>Fecha y hora</label>
                    <input type="datetime-local" class="form-control" name="issue_date" id="issue_date" required>
                </div>

                <div class="col-md-2 mb-3">
                    <label>Tipo</label>
                    <select class="form-select" name="receipt_type_name" id="receipt_type_name" required>
                        <option value="Comprobante interno" hidden>-</option>
                        @if(str_contains(strtolower((string) config('arca.vat_condition')), 'monotriab'))
                        <option value="Factura C">Factura C</option>
                        @else
                        <option value="Factura A">Factura A</option>
                        <option value="Factura B">Factura B</option>
                        <option value="Factura C">Factura C</option>
                        @endif
                    </select>
                </div>

                <div class="col-md-1 mb-3">
                    <label>Letra</label>
                    <input class="form-control" name="letter" id="invoice_letter" value="C" readonly required>
                </div>

                <div class="col-md-3 mb-3">
                    <label>Punto de venta</label>
                    <select class="form-select" name="first_number" id="first_number" required>
                        <option value="">Cargando...</option>
                    </select>
                </div>

            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Agregar producto</h5>
            <small class="text-muted">Talle/Color: Enter inicia, Tab recorre opciones y Enter confirma.</small>
        </div>

        <div class="card-body">
            <div class="row">

                <div class="col-md-4 mb-3">
                    <label>Producto</label>
                    <select class="form-select" id="product_id" data-remote-url="{{ url('/api/products') }}" data-remote-minimum="2" data-remote-placeholder="Buscar por nombre, código o código de barras...">
                        <option value=""></option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label>Talle</label>
                    <select class="form-select" id="size_id">
                        <option value="">Seleccione producto...</option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label>Color</label>
                    <select class="form-select" id="color_id">
                        <option value="">Seleccione talle...</option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label>Cantidad</label>
                    <input type="number" class="form-control" id="quantity" value="1" min="1">
                </div>

                <div class="col-md-2 mb-3">
                    <label>Desc. %</label>
                    <input type="number" class="form-control" id="discount_percentage" value="0">
                </div>

            </div>

            <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <strong>Stock:</strong> <span id="current_stock">0</span>
                </div>

                <div>
                    <strong>Precio:</strong> <span id="unit_price">$0,00</span>
                </div>

                <div>
                    <strong>Total línea:</strong> <span id="line_total">$0,00</span>
                </div>

                <button type="button" class="btn btn-primary btn-sm" id="addItemBtn">
                    Agregar producto
                </button>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Detalle</h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">Precio</th>
                        <th class="text-end">Desc.</th>
                        <th class="text-end">Total</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody id="itemsRows">
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Sin productos agregados
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Percepción impositiva <small class="text-muted">(opcional)</small></h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Tipo</label><select id="perception_type" class="form-select">
                        <option value="">Sin percepción</option>
                        <option>IVA</option>
                        <option>Ingresos Brutos</option>
                        <option>Otra</option>
                    </select></div>
                <div class="col-md-3"><label class="form-label">Régimen</label><input id="perception_regime" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Importe aplicado</label><input id="perception_amount" type="number" min="0" step="0.01" value="0" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Importe calculado</label><input id="perception_calculated" type="number" min="0" step="0.01" value="0" class="form-control"></div>
            </div>
        </div>
    </div>

    <div class="row justify-content-end">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Total</h5>
                        <h3 class="mb-0" id="invoiceTotal">$0,00</h3>
                    </div>

                    <hr>

                    <button class="btn btn-success w-100">
                        Continuar a forma de pago
                    </button>
                </div>
            </div>
        </div>
    </div>

</form>

<script>
    let products = [];
    let selectedProduct = null;
    let variants = [];
    let selectedVariant = null;
    let invoiceItems = [];
    let clients = [];
    let draftPromotions = [];
    let selectedPromotionId = null;
    let promotionTimer = null;
    let scannedProductCode = null;
    const issuerVatCondition = @json(config('arca.vat_condition', 'MONOTRIBUTO'));
    const defaultPointOfSale = String(@json(config('arca.pto_vta', 4))).padStart(4, '0');
    const saleDraftStorageKey = 'sales.invoice.current_draft';
    const shouldRestoreSaleDraft = new URLSearchParams(window.location.search).get('restore_draft') === '1';

    function rememberSaleDraft() {
        const client = client_id._remoteSelected ?? clients.find(item => Number(item.id) === Number(client_id.value));
        sessionStorage.setItem(saleDraftStorageKey, JSON.stringify({
            client,
            issue_date: document.querySelector('[name="issue_date"]')?.value,
            receipt_type_name: receipt_type_name.value,
            letter: invoice_letter.value,
            first_number: first_number.value,
            selectedPromotionId,
            invoiceItems
        }));
    }

    function restoreSaleDraft() {
        const raw = sessionStorage.getItem(saleDraftStorageKey);
        if (!raw) return;
        try {
            const draft = JSON.parse(raw);
            if (draft.client?.id) selectClient(draft.client);
            if (draft.issue_date) document.querySelector('[name="issue_date"]').value = draft.issue_date;
            if (draft.first_number) first_number.value = draft.first_number;
            if (draft.receipt_type_name) receipt_type_name.value = draft.receipt_type_name;
            if (draft.letter) invoice_letter.value = draft.letter;
            selectedPromotionId = draft.selectedPromotionId ?? null;
            invoiceItems = Array.isArray(draft.invoiceItems) ? draft.invoiceItems : [];
            renderItems();
        } catch (error) {
            console.warn('No se pudo restaurar el borrador de venta.', error);
        }
    }

    function money(value) {
        return Number(value ?? 0).toLocaleString('es-AR', {
            style: 'currency',
            currency: 'ARS'
        });
    }

    async function cargarClientes() {
        erpEnhanceRemoteSelect(client_id);
        client_id.addEventListener('change', async () => {
            let client = client_id._remoteSelected;
            if (!client && client_id.value) {
                const response = await fetch(`${window.APP_BASE_URL}/api/clients/${client_id.value}`);
                if (response.ok) client = await response.json();
            }
            clients = client ? [client] : [];
            if (client) applyFiscalData(client);
        });

        const createdClientId = sessionStorage.getItem('invoice.new_client_id');
        sessionStorage.removeItem('invoice.new_client_id');
        const endpoint = createdClientId ?
            `${window.APP_BASE_URL}/api/clients/${createdClientId}` :
            `${window.APP_BASE_URL}/api/clients/default-consumer`;
        const response = await fetch(endpoint, {
            headers: {
                Accept: 'application/json'
            }
        });
        if (response.ok) selectClient(await response.json());
    }

    function selectClient(client) {
        const label = [client.code, client.name, client.document_number].filter(Boolean).join(' · ');
        client_id.innerHTML = '';
        client_id.add(new Option(label, client.id, true, true));
        client_id._remoteSelected = client;
        if (client_id._remoteSelect) client_id._remoteSelect.input.value = label;
        client_id.dispatchEvent(new Event('change', {
            bubbles: true
        }));
    }

    function fiscalLetterFor(client) {
        const issuer = String(issuerVatCondition).toLowerCase();
        if (issuer.includes('monotrib') || issuer.includes('exent')) return 'C';

        const receiver = String(client?.vat_classification ?? '').toLowerCase();
        return receiver.includes('resp') || receiver.includes('inscrip') || receiver.includes('monotrib') ?
            'A' :
            'B';
    }

    function applyFiscalData(client) {
        if (first_number.value === '0099') {
            receipt_type_name.value = 'Comprobante interno';
            invoice_letter.value = '-';
            receipt_type_name.disabled = true;
            return;
        }

        receipt_type_name.disabled = false;
        const letter = fiscalLetterFor(client);
        receipt_type_name.value = `Factura ${letter}`;
        invoice_letter.value = letter;
    }

    async function cargarPuntosVenta() {
        first_number.innerHTML = `
            <option value="${defaultPointOfSale}">${defaultPointOfSale}</option>
            <option value="0099">0099</option>
        `;
    }

    function actualizarTipoPorPuntoVenta() {
        const client = clients.find(item => Number(item.id) === Number(client_id.value))
            ?? client_id._remoteSelected
            ?? null;

        applyFiscalData(client);
    }

    async function cargarProductos() {
        const remote = erpEnhanceRemoteSelect(product_id);
        remote.input.addEventListener('keydown', handleProductScanner);
    }

    async function cargarProductoCompleto(productId) {
        const res = await fetch(`${window.APP_BASE_URL}/api/products/${productId}`);
        selectedProduct = await res.json();

        if (isGenericSaleProduct(selectedProduct)) {
            selectedVariant = {
                id: null,
                sku: selectedProduct.code || selectedProduct.reference_code || selectedProduct.bar_code,
                bar_code: selectedProduct.bar_code || selectedProduct.code,
                price_a_with_tax: selectedProduct.price_a_with_tax || 0,
                current_stock: null,
                size: null,
                color: null,
                is_generic: true
            };
            variants = [];
            size_id.innerHTML = '<option value="">No aplica</option>';
            color_id.innerHTML = '<option value="">No aplica</option>';
            size_id.disabled = true;
            color_id.disabled = true;
            actualizarLinea();
            return;
        }

        variants = (selectedProduct.variants ?? [])
            .filter(v => Number(v.current_stock ?? 0) > 0);

        limpiarColorYVariante();

        if (selectedProduct.has_variants === false) {
            selectedVariant = variants.find(v => !v.size_id && !v.color_id) ?? null;
            size_id.innerHTML = '<option value="">No aplica</option>';
            color_id.innerHTML = '<option value="">No aplica</option>';
            size_id.disabled = true;
            color_id.disabled = true;
            actualizarLinea();
            return;
        }

        color_id.disabled = false;
        cargarTalles();
    }

    function isGenericSaleProduct(product) {
        const genericCodes = new Set(['ZZ', '00', '0000']);
        return [product?.code, product?.reference_code, product?.bar_code]
            .some(value => genericCodes.has(String(value ?? '').trim().toUpperCase()));
    }

    async function handleProductScanner(event) {
        if (event.key !== 'Enter') return;

        event.preventDefault();
        const code = event.currentTarget.value.trim();
        if (!code) return;

        const response = await fetch(
            `${window.APP_BASE_URL}/api/products?lookup=1&per_page=20&search=${encodeURIComponent(code)}`, {
                headers: {
                    Accept: 'application/json'
                }
            }
        );
        if (!response.ok) return;

        const payload = await response.json();
        const matches = payload.data ?? payload;
        const exactProduct = matches.find(product => [product.bar_code, product.code, product.reference_code]
            .some(value => String(value ?? '').toLowerCase() === code.toLowerCase()) ||
            (product.variants ?? []).some(variant => [variant.bar_code, variant.sku]
                .some(value => String(value ?? '').toLowerCase() === code.toLowerCase())
            )
        );

        if (!exactProduct) {
            const firstProduct = matches[0];
            if (firstProduct) product_id._remoteSelect.choose(firstProduct);
            return;
        }

        scannedProductCode = code;
        product_id._remoteSelect.choose(exactProduct);
    }

    function finishScannedProduct() {
        if (!scannedProductCode || !selectedProduct) return false;

        if (selectedProduct.has_variants !== false && !isGenericSaleProduct(selectedProduct)) {
            const exactVariant = variants.find(variant => [variant.bar_code, variant.sku].some(value =>
                String(value ?? '').toLowerCase() === scannedProductCode.toLowerCase()
            ));

            if (!exactVariant) {
                scannedProductCode = null;
                return false;
            }

            selectedVariant = exactVariant;
            actualizarLinea();
        }

        const added = agregarItem();
        scannedProductCode = null;

        if (added) {
            product_id._remoteSelect.clear();
            selectedProduct = null;
            selectedVariant = null;
            variants = [];
            size_id.innerHTML = '<option value="">Seleccione producto...</option>';
            color_id.innerHTML = '<option value="">Seleccione talle...</option>';
            size_id.disabled = false;
            color_id.disabled = false;
            actualizarLinea();
            product_id._remoteSelect.input.focus();
        }

        return added;
    }

    function cargarTalles() {
        const sizes = [];
        const hasVariantsWithoutSize = variants.some(v => !v.size_id);

        variants.forEach(v => {
            if (v.size && !sizes.some(s => Number(s.id) === Number(v.size.id))) {
                sizes.push(v.size);
            }
        });

        size_id.innerHTML = '<option value="">Seleccione talle...</option>';

        if (hasVariantsWithoutSize) {
            size_id.innerHTML += '<option value="__none__">Sin talle</option>';
        }

        sizes.forEach(size => {
            size_id.innerHTML += `<option value="${size.id}">${size.name}</option>`;
        });

        size_id.disabled = sizes.length === 0 && hasVariantsWithoutSize;

        if (sizes.length === 0 && hasVariantsWithoutSize) {
            size_id.value = '__none__';
            cargarColoresPorTalle();
        }
    }

    function cargarColoresPorTalle() {
        const selectedSize = size_id.value;

        if (!selectedSize) {
            color_id.innerHTML = '<option value="">Seleccione talle...</option>';
            selectedVariant = null;
            actualizarLinea();
            return;
        }

        const filtered = variants.filter(v => selectedSize === '__none__' ?
            !v.size_id :
            Number(v.size_id) === Number(selectedSize)
        );
        const colors = [];

        filtered.forEach(v => {
            if (v.color && !colors.some(c => Number(c.id) === Number(v.color.id))) {
                colors.push(v.color);
            }
        });

        color_id.innerHTML = '<option value="">Seleccione color...</option>';

        colors.forEach(color => {
            color_id.innerHTML += `<option value="${color.id}">${color.name}</option>`;
        });

        selectedVariant = null;
        actualizarLinea();
    }

    function seleccionarVariante() {
        selectedVariant = variants.find(v =>
            (size_id.value === '__none__' ?
                !v.size_id :
                Number(v.size_id) === Number(size_id.value)) &&
            Number(v.color_id) === Number(color_id.value)
        );

        actualizarLinea();
    }

    function limpiarColorYVariante() {
        color_id.innerHTML = '<option value="">Seleccione talle...</option>';
        size_id.disabled = false;
        selectedVariant = null;
        actualizarLinea();
    }

    function resetProductEntry() {
        product_id._remoteSelect?.clear();
        selectedProduct = null;
        selectedVariant = null;
        variants = [];
        size_id.innerHTML = '<option value="">Seleccione producto...</option>';
        color_id.innerHTML = '<option value="">Seleccione talle...</option>';
        size_id.disabled = false;
        color_id.disabled = false;
        quantity.value = 1;
        discount_percentage.value = 0;
        actualizarLinea();
        requestAnimationFrame(() => product_id._remoteSelect?.input.focus());
    }

    function finishVariantSelectMode(select) {
        delete select.dataset.keyboardSelecting;
        select.classList.remove('border-primary', 'shadow-sm');
    }

    function handleVariantSelectKeyboard(event, nextControl) {
        const select = event.currentTarget;
        const options = [...select.options].filter(option => option.value !== '');
        if (!options.length) return;

        const selecting = select.dataset.keyboardSelecting === 'true';

        if (event.key === 'Enter') {
            event.preventDefault();

            if (!selecting) {
                select.dataset.keyboardSelecting = 'true';
                select.classList.add('border-primary', 'shadow-sm');

                if (!select.value) {
                    select.value = options[0].value;
                    select.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
                return;
            }

            finishVariantSelectMode(select);
            requestAnimationFrame(() => nextControl?.focus());
            return;
        }

        if (event.key === 'Tab' && selecting) {
            event.preventDefault();
            const currentIndex = Math.max(0, options.findIndex(option => option.value === select.value));
            const direction = event.shiftKey ? -1 : 1;
            const nextIndex = (currentIndex + direction + options.length) % options.length;
            select.value = options[nextIndex].value;
            select.dispatchEvent(new Event('change', {
                bubbles: true
            }));
            return;
        }

        if (event.key === 'Escape' && selecting) {
            event.preventDefault();
            finishVariantSelectMode(select);
        }
    }

    function addItemAndContinue() {
        if (!agregarItem()) return false;
        resetProductEntry();
        return true;
    }

    function actualizarLinea() {
        const qty = Number(quantity.value || 0);
        const discount = Number(discount_percentage.value || 0);

        if (!selectedVariant) {
            current_stock.innerText = '0';
            unit_price.innerText = money(0);
            line_total.innerText = money(0);
            return;
        }

        const price = Number(selectedVariant.price_a_with_tax || selectedProduct.price_a_with_tax || 0);
        let total = price * qty;
        total -= total * discount / 100;

        current_stock.innerText = selectedVariant.is_generic
            ? 'No controla stock'
            : (selectedVariant.current_stock ?? 0);
        unit_price.innerText = money(price);
        line_total.innerText = money(total);
    }

    function agregarItem() {
        if (!selectedProduct || !selectedVariant) {
            alert('Seleccioná una variante válida del producto.');
            return false;
        }

        const isGeneric = isGenericSaleProduct(selectedProduct) && selectedVariant.is_generic;

        // Validar: SKU o código de barras obligatorios
        const hasSku = Boolean(selectedVariant.sku && String(selectedVariant.sku).trim() !== '');
        const hasBarCode = Boolean(selectedVariant.bar_code && String(selectedVariant.bar_code).trim() !== '');

        if (!isGeneric && !hasSku && !hasBarCode) {
            alert('La variante debe tener SKU o Código de barras.');
            return false;
        }

        // Validar que la variante no esté totalmente vacía (precio o stock)
        const hasPrice = Boolean((selectedVariant.price_a_with_tax ?? selectedProduct.price_a_with_tax) !== undefined && (selectedVariant.price_a_with_tax ?? selectedProduct.price_a_with_tax) !== null && String(selectedVariant.price_a_with_tax ?? selectedProduct.price_a_with_tax) !== '');
        const hasStock = Number(selectedVariant.current_stock ?? 0) > 0;

        if (!isGeneric && !hasPrice && !hasStock) {
            alert('La variante debe tener precio o stock además de SKU o Código de barras.');
            return false;
        }

        const qty = Number(quantity.value || 0);
        const discount = Number(discount_percentage.value || 0);
        const stock = Number(selectedVariant.current_stock || 0);
        const price = Number(selectedVariant.price_a_with_tax || selectedProduct.price_a_with_tax || 0);

        if (qty <= 0) {
            alert('La cantidad debe ser mayor a cero.');
            return false;
        }

        if (!isGeneric && qty > stock) {
            alert('La cantidad supera el stock disponible.');
            return false;
        }

        let total = price * qty;
        total -= total * discount / 100;

        invoiceItems.push({
            product_id: Number(selectedProduct.id),
            product_variant_id: selectedVariant.id ? Number(selectedVariant.id) : null,
            product_name: selectedProduct.name,
            size_name: selectedVariant.size?.name ?? '',
            color_name: selectedVariant.color?.name ?? '',
            quantity: qty,
            available_stock: isGeneric ? null : stock,
            controls_stock: !isGeneric,
            is_generic: isGeneric,
            discount_percentage: discount,
            price,
            total
        });

        renderItems();
        return true;
    }

    function renderItems() {
        if (!invoiceItems.length) {
            itemsRows.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    Sin productos agregados
                </td>
            </tr>
        `;

            invoiceTotal.innerText = money(0);
            evaluateDraftPromotions();
            return;
        }

        itemsRows.innerHTML = invoiceItems.map((item, index) => `
        <tr>
            <td>
                <strong>${item.product_name}</strong><br>
                <small class="text-muted">
                    Talle: ${item.size_name || '-'} · Color: ${item.color_name || '-'}
                </small>
            </td>

            <td class="text-end">${item.quantity}</td>
            <td class="text-end">
                <strong>${money(item.price)}</strong>
                ${item.manual_price ? '<br><small class="text-warning">Precio manual</small>' : ''}
            </td>
            <td class="text-end">${item.discount_percentage}%</td>
            <td class="text-end"><strong>${money(item.total)}</strong></td>

            <td class="text-end text-nowrap">
                <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center" onclick="editInvoiceItem(${index})" title="Editar renglón" aria-label="Editar renglón">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 20h9"></path>
                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                        </svg>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center" onclick="removeItem(${index})" title="Quitar producto" aria-label="Quitar producto">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 6h18"></path>
                            <path d="M8 6V4h8v2"></path>
                            <path d="M19 6l-1 14H6L5 6"></path>
                            <path d="M10 11v5M14 11v5"></path>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');

        const total = invoiceItems.reduce((acc, item) => acc + item.total, 0);
        invoiceTotal.innerText = money(total);
        evaluateDraftPromotions();
    }

    function evaluateDraftPromotions() {
        clearTimeout(promotionTimer);
        if (!invoiceItems.length) {
            draftPromotionsBox.innerHTML = '<span class="text-muted">Agregá productos para consultar promociones.</span>';
            return;
        }
        promotionTimer = setTimeout(async () => {
            const client = clients.find(c => Number(c.id) === Number(client_id.value));
            const response = await fetch(`${window.APP_BASE_URL}/api/sales-promotions/compatible`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify({
                    date: document.querySelector('[name="issue_date"]').value,
                    currency_name: client?.currency ?? 'Pesos',
                    branch_name: client?.branch_origin ?? null,
                    price_list_name: client?.price_type ?? null,
                    items: invoiceItems.map(i => ({
                        product_id: i.product_id,
                        quantity: i.quantity,
                        unit_price: i.price
                    }))
                })
            });
            if (!response.ok) return;
            draftPromotions = await response.json();
            draftPromotionsBox.innerHTML = draftPromotions.length ? draftPromotions.map(p => `<div class="border rounded p-3 mb-2 d-flex justify-content-between"><div><strong>${p.name}</strong><br><small class="text-muted">Ahorro estimado ${money(p.estimated_discount)}</small></div><button type="button" class="btn btn-sm ${Number(selectedPromotionId)===Number(p.id)?'btn-success':'btn-outline-primary'}" onclick="selectDraftPromotion(${p.id})">${Number(selectedPromotionId)===Number(p.id)?'Seleccionada':'Elegir'}</button></div>`).join('') : '<span class="text-muted">No hay promociones compatibles.</span>'
        }, 250);
    }

    function selectDraftPromotion(id) {
        selectedPromotionId = Number(selectedPromotionId) === Number(id) ? null : id;
        evaluateDraftPromotions()
    }

    function removeItem(index) {
        invoiceItems.splice(index, 1);
        renderItems();
    }

    async function editInvoiceItem(index) {
        const item = invoiceItems[index];
        if (!item) return;

        const editableNumber = value => {
            const number = Number(value || 0);
            return Number.isInteger(number)
                ? String(number)
                : String(Math.round(number * 100) / 100);
        };

        const moveCaretToEnd = input => {
            input.focus();
            // Los input number no exponen setSelectionRange en todos los navegadores.
            // Reasignar el valor ubica el cursor al final sin alterar el número.
            const value = input.value;
            input.value = '';
            input.value = value;
        };

        const result = await Swal.fire({
            title: 'Editar producto',
            html: `
                <div class="text-start mb-3"><strong>${item.product_name}</strong></div>
                <div class="row g-3 text-start">
                    <div class="col-12">
                        <label class="form-label" for="saleEditPrice">Precio unitario</label>
                        <input id="saleEditPrice" class="form-control" type="number" min="0" step="0.01" value="${editableNumber(item.price)}">
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="saleEditQuantity">Cantidad</label>
                        <input id="saleEditQuantity" class="form-control" type="number" min="0.01" step="0.01" value="${editableNumber(item.quantity)}">
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="saleEditDiscount">Descuento %</label>
                        <input id="saleEditDiscount" class="form-control" type="number" min="0" max="100" step="0.01" value="${editableNumber(item.discount_percentage)}">
                    </div>
                </div>`,
            showCancelButton: true,
            confirmButtonText: 'Guardar cambios',
            cancelButtonText: 'Cancelar',
            focusConfirm: false,
            didOpen: () => {
                const fields = [
                    document.getElementById('saleEditPrice'),
                    document.getElementById('saleEditQuantity'),
                    document.getElementById('saleEditDiscount'),
                ].filter(Boolean);

                fields.forEach((field, fieldIndex) => {
                    field.addEventListener('keydown', event => {
                        if (event.key !== 'Enter') return;

                        event.preventDefault();
                        event.stopPropagation();

                        const direction = event.shiftKey ? -1 : 1;
                        const nextIndex = fieldIndex + direction;
                        if (nextIndex >= 0 && nextIndex < fields.length) {
                            moveCaretToEnd(fields[nextIndex]);
                            return;
                        }

                        if (!event.shiftKey) Swal.clickConfirm();
                    });
                });

                requestAnimationFrame(() => moveCaretToEnd(fields[0]));
            },
            preConfirm: () => {
                const price = Number(document.getElementById('saleEditPrice').value);
                const quantity = Number(document.getElementById('saleEditQuantity').value);
                const discount = Number(document.getElementById('saleEditDiscount').value);
                if (!Number.isFinite(price) || price < 0) {
                    Swal.showValidationMessage('Ingresá un precio válido mayor o igual a cero.');
                    return false;
                }
                if (!Number.isFinite(quantity) || quantity <= 0) {
                    Swal.showValidationMessage('La cantidad debe ser mayor a cero.');
                    return false;
                }
                if (item.controls_stock !== false && Number.isFinite(Number(item.available_stock)) && quantity > Number(item.available_stock)) {
                    Swal.showValidationMessage(`La cantidad supera el stock disponible (${item.available_stock}).`);
                    return false;
                }
                if (!Number.isFinite(discount) || discount < 0 || discount > 100) {
                    Swal.showValidationMessage('El descuento debe estar entre 0 y 100%.');
                    return false;
                }
                return { price, quantity, discount };
            }
        });

        if (!result.isConfirmed) return;

        const priceWasChanged = Math.abs(Number(item.price) - result.value.price) > 0.0001;
        item.price = result.value.price;
        item.quantity = result.value.quantity;
        item.discount_percentage = result.value.discount;
        item.manual_price = item.manual_price || priceWasChanged;
        item.total = item.price * Number(item.quantity || 0);
        item.total -= item.total * Number(item.discount_percentage || 0) / 100;
        renderItems();
        rememberSaleDraft();
    }

    document.addEventListener('DOMContentLoaded', async () => {
        const issueDate = document.getElementById('issue_date');
        if (issueDate && !issueDate.value) {
            const localNow = new Date();
            localNow.setMinutes(localNow.getMinutes() - localNow.getTimezoneOffset());
            issueDate.value = localNow.toISOString().slice(0, 16);
        }

        await cargarClientes();
        await cargarProductos();
        await cargarPuntosVenta();
        if (shouldRestoreSaleDraft) {
            restoreSaleDraft();
        } else {
            sessionStorage.removeItem(saleDraftStorageKey);
        }

        first_number.addEventListener('change', actualizarTipoPorPuntoVenta);

        receipt_type_name.addEventListener('change', () => {
            invoice_letter.value = receipt_type_name.value.replace('Factura ', '').trim();
        });

        product_id.addEventListener('change', async e => {
            if (e.target.value) {
                await cargarProductoCompleto(e.target.value);
                const addedByScanner = finishScannedProduct();
                if (!addedByScanner) {
                    requestAnimationFrame(() => {
                        if (selectedProduct?.has_variants === false || isGenericSaleProduct(selectedProduct)) quantity.focus();
                        else if (size_id.disabled) color_id.focus();
                        else size_id.focus();
                    });
                }
            }
        });

        size_id.addEventListener('change', cargarColoresPorTalle);
        color_id.addEventListener('change', seleccionarVariante);
        quantity.addEventListener('input', actualizarLinea);
        discount_percentage.addEventListener('input', actualizarLinea);
        addItemBtn.addEventListener('click', addItemAndContinue);

        size_id.addEventListener('keydown', event => handleVariantSelectKeyboard(event, color_id));
        color_id.addEventListener('keydown', event => handleVariantSelectKeyboard(event, quantity));
        size_id.addEventListener('blur', () => finishVariantSelectMode(size_id));
        color_id.addEventListener('blur', () => finishVariantSelectMode(color_id));
        quantity.addEventListener('keydown', event => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            discount_percentage.focus();
            discount_percentage.select();
        });
        discount_percentage.addEventListener('keydown', event => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            addItemBtn.click();
        });

        client_id.addEventListener('change', evaluateDraftPromotions);
        document.querySelector('[name="issue_date"]').addEventListener('change', evaluateDraftPromotions);
    });

    saleForm.addEventListener('submit', async e => {
        e.preventDefault();

        if (!invoiceItems.length) {
            alert('Agregá al menos un producto.');
            return;
        }

        const form = new FormData(e.target);

        const payload = {
            client_id: Number(form.get('client_id')),
            issue_date: form.get('issue_date'),
            receipt_type_name: form.get('receipt_type_name'),
            letter: form.get('letter'),
            first_number: form.get('first_number'),
            sales_promotion_id: selectedPromotionId,
            items: invoiceItems.map(item => ({
                product_id: item.product_id,
                product_variant_id: item.product_variant_id,
                quantity: item.quantity,
                discount_percentage: item.discount_percentage,
                unit_price_with_taxes: item.price
            }))
        };
        rememberSaleDraft();
        if (perception_type.value && Number(perception_amount.value) > 0) payload.perceptions = [{
            tax_type: perception_type.value,
            regime_name: perception_regime.value || perception_type.value,
            amount: Number(perception_amount.value),
            calculated_amount: Number(perception_calculated.value || perception_amount.value),
            is_automatic: false
        }];

        const res = await fetch(`${window.APP_BASE_URL}/api/invoices`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json'
            },
            body: JSON.stringify(payload)
        });

        if (res.ok) {
            const invoice = await res.json();
            location.href = `${window.APP_BASE_URL}/demo/sales/${invoice.id}/payment-method`;
        } else {
            const error = await res.json();
            console.log(error);
            alert(error.message ?? 'Error al guardar factura');
        }
    });
</script>

@endsection
