{{-- Vista: Nuevo registro de Productos. Muestra el formulario para crear un registro de Productos. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Nuevo Producto</h4>
        <small class="text-muted">Alta de producto, relaciones, precios e imagen</small>
    </div>

    <a href="{{ url('/demo/products') }}" class="btn btn-secondary">
        <i class="ri-arrow-left-line me-1"></i>
        Volver
    </a>
</div>

<form id="form" enctype="multipart/form-data">

    <div class="row">

        <div class="col-lg-8">

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Datos principales</h5>
                </div>

                <div class="card-body">
                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre *</label>
                            <input
                                class="form-control"
                                name="name"
                                placeholder="Nombre del producto"
                                required>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Código / SKU</label>
                            <input
                                class="form-control"
                                id="product_code"
                                name="code"
                                placeholder="Ej: REM-001">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Código de barras</label>
                            <input
                                class="form-control"
                                id="product_bar_code"
                                name="bar_code"
                                placeholder="Ej: 779...">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Código de referencia</label>
                            <input
                                class="form-control"
                                name="reference_code"
                                placeholder="Referencia interna">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Moneda</label>
                            <select class="form-select" name="currency_name">
                                <option value="Pesos">Pesos</option>
                                <option value="Dólares">Dólares</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Símbolo</label>
                            <input
                                class="form-control"
                                name="currency_symbol"
                                value="$">
                        </div>

                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Clasificación</h5>
                </div>

                <div class="card-body">
                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Marca</label>
                            <select class="form-select" name="brand_id" id="brand_id">
                                <option value="">Cargando...</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Categoría</label>
                            <select class="form-select" name="category_id" id="category_id">
                                <option value="">Cargando...</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Modelo</label>
                            <select class="form-select" name="product_model_id" id="product_model_id">
                                <option value="">Cargando...</option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1">Precios e impuestos</h5>
                        <small class="text-muted">
                            Cargá directamente los precios netos y finales de cada lista.
                        </small>
                    </div>

                    <div style="width:180px;">
                        <label class="form-label mb-1">Alícuota IVA</label>

                        <select
                            class="form-select form-select-sm"
                            id="tax_aliquot_percentage">
                            <option value="21">21%</option>
                            <option value="10.5">10,5%</option>
                            <option value="27">27%</option>
                            <option value="0">Exento / 0%</option>
                        </select>

                        <input
                            type="hidden"
                            name="aliquot_name"
                            id="aliquot_name"
                            value="IVA 21%">

                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="auto_calculate_tax"
                                name="auto_calculate_tax" value="1">
                            <label class="form-check-label small" for="auto_calculate_tax">
                                Calcular precios finales automáticamente
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:100px;">Lista</th>
                                    <th>Precio neto</th>
                                    <th>Precio final</th>
                                </tr>
                            </thead>

                            <tbody>

                                <tr class="table-light">
                                    <td><span class="badge bg-label-secondary">Costo</span></td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input class="form-control net-price" id="cost_with_discount"
                                                name="cost_with_discount" type="number" min="0" step="0.01" placeholder="0,00">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input class="form-control tax-inclusive-price"
                                                id="cost_with_discount_with_tax_aliquot"
                                                name="cost_with_discount_with_tax_aliquot" type="number" min="0" step="0.01">
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <span class="badge bg-label-primary">Precio A</span>
                                    </td>

                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input
                                                class="form-control net-price"
                                                id="price_a"
                                                name="price_a"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="0,00">
                                        </div>
                                    </td>

                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input
                                                class="form-control tax-inclusive-price"
                                                id="price_a_with_tax"
                                                name="price_a_with_tax"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="0,00">
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <span class="badge bg-label-info">Precio B</span>
                                    </td>

                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input
                                                class="form-control net-price"
                                                id="price_b"
                                                name="price_b"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="0,00">
                                        </div>
                                    </td>

                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input
                                                class="form-control tax-inclusive-price"
                                                id="price_b_with_tax"
                                                name="price_b_with_tax"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="0,00">
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <span class="badge bg-label-warning">Precio C</span>
                                    </td>

                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input
                                                class="form-control net-price"
                                                id="price_c"
                                                name="price_c"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="0,00">
                                        </div>
                                    </td>

                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input
                                                class="form-control tax-inclusive-price"
                                                id="price_c_with_tax"
                                                name="price_c_with_tax"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="0,00">
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <span class="badge bg-label-secondary">Precio D</span>
                                    </td>

                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input
                                                class="form-control net-price"
                                                id="price_d"
                                                name="price_d"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="0,00">
                                        </div>
                                    </td>

                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input
                                                class="form-control tax-inclusive-price"
                                                id="price_d_with_tax"
                                                name="price_d_with_tax"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="0,00">
                                        </div>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-light border mt-3 mb-0">
                        Los <strong>precios finales</strong> se pueden ingresar manualmente.
                        Activá el cálculo automático solamente si querés obtenerlos desde el precio neto y la alícuota seleccionada.
                    </div>

                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Peso y dimensiones</h5>
                </div>

                <div class="card-body">
                    <div class="row">

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Peso</label>

                            <div class="input-group">
                                <input
                                    class="form-control"
                                    name="weight"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0,00">
                                <span class="input-group-text">kg</span>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Altura</label>

                            <div class="input-group">
                                <input
                                    class="form-control"
                                    name="height"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0,00">
                                <span class="input-group-text">cm</span>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Ancho</label>

                            <div class="input-group">
                                <input
                                    class="form-control"
                                    name="width"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0,00">
                                <span class="input-group-text">cm</span>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Largo</label>

                            <div class="input-group">
                                <input
                                    class="form-control"
                                    name="length"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0,00">
                                <span class="input-group-text">cm</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <div class="col-lg-4">

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Imágenes del producto</h5>
                </div>

                <div class="card-body">

                    <div id="imagePreviews" class="row g-2 mb-3">
                        <div class="col-12 text-center text-muted py-4 border rounded">
                            <i class="ri-image-line" style="font-size:48px;"></i>
                            <div>Sin imágenes seleccionadas</div>
                        </div>
                    </div>

                    <label class="form-label">Seleccionar imágenes</label>

                    <input
                        class="form-control"
                        name="images[]"
                        id="images"
                        type="file"
                        accept="image/*"
                        multiple>

                    <small class="text-muted d-block mt-2">
                        Imagen opcional. Tamaño máximo: 2 MB.
                    </small>

                    @include('components.product-image-url-manager')
                </div>
            </div>

            <div class="card">
                <div class="card-body">

                    <div class="d-grid gap-2">
                        <button id="submitButton" class="btn btn-success" type="submit">
                            <i class="ri-save-line me-1"></i>
                            Guardar producto
                        </button>

                        <a href="{{ url('/demo/products') }}" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>

                </div>
            </div>

        </div>

    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-1">Manejo de stock</h5>
            <small class="text-muted">Elegí si el producto se controla por talle/color o como una única unidad.</small>
        </div>
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-6 mb-3">
                    <label class="form-label d-block">Producto con variantes</label>
                    <input type="hidden" name="has_variants" value="0">
                    <div class="d-flex align-items-center gap-3 border rounded px-3 py-2">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                name="has_variants" id="has_variants" value="1" checked>
                            <label class="form-check-label" for="has_variants">Sí</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3" id="unitStockBox" hidden>
                    <label class="form-label">Stock inicial unitario</label>
                    <input class="form-control" name="current_stock" id="unit_current_stock"
                        type="number" min="0" step="0.01" value="0" disabled>
                </div>
                <div class="col-md-12">
                    <div class="alert alert-info mb-0" id="stockModeHelp">
                        El stock se controlará por cada combinación de talle y color.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4" id="variantsCard">
        <div class="card-header">
            <h5 class="mb-1">Variantes del producto</h5>
            <small class="text-muted">
                Seleccioná uno o varios talles; el precio se toma del producto padre.
            </small>
        </div>

        <div class="card-body">
            <div class="row g-3 align-items-start">
                <div class="col-lg-5">
                    <x-multi-select
                        id="variant_size_ids"
                        name="variant_size_ids[]"
                        label="Talles"
                        :remote-url="url('/api/sizes')"
                        :minimum-characters="1"
                        :search-only="true"
                        help="Ingresá desde un carácter y acumulá los talles como etiquetas." />
                </div>

                <div class="col-sm-6 col-lg-3">
                    <label class="form-label">Color</label>
                    <select class="form-select" id="variant_color_id">
                        <option value="">Cargando...</option>
                    </select>
                </div>

                <div class="col-sm-3 col-lg-2">
                    <label class="form-label">Código color</label>
                    <input class="form-control text-uppercase" id="variant_color_code" maxlength="3" placeholder="NGO">
                    <small class="text-muted">Editable antes de generar.</small>
                </div>

                <div class="col-sm-3 col-lg-2">
                    <label class="form-label">Stock inicial</label>
                    <input class="form-control" id="variant_current_stock" type="number" min="0" step="0.01" value="0">
                </div>

                <div class="col-12">
                </div>

                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Imagen desde archivo</label>
                            <input class="form-control" id="variant_image" type="file" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Imagen mediante URL</label>
                            <div class="input-group">
                        <input class="form-control" id="variant_image_url" type="url" placeholder="https://...">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 d-grid align-self-end">
                    <button type="button" id="addVariantButton" class="btn btn-outline-success">
                    <i class="ri-add-line me-1"></i>
                    Agregar variante a la lista
                    </button>
                </div>
            </div>

            <div class="table-responsive mt-4">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Talle</th>
                            <th>Color</th>
                            <th>SKU</th>
                            <th>Código de barras</th>
                            <th class="text-end">Precio final</th>
                            <th class="text-center">Stock</th>
                            <th>Imagen</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="newVariantRows">
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Todavía no agregaste variantes.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</form>

<script>
    const pendingVariants = [];

    function updateStockMode() {
        const variantsSwitch = document.getElementById('has_variants');
        const usesVariants = variantsSwitch.checked;
        document.getElementById('variantsCard').hidden = !usesVariants;
        document.getElementById('unitStockBox').hidden = usesVariants;
        document.getElementById('unit_current_stock').disabled = usesVariants;
        variantsSwitch.nextElementSibling.textContent = usesVariants ? 'Sí' : 'No';
        document.getElementById('stockModeHelp').textContent = usesVariants ?
            'El stock se controlará por cada combinación de talle y color.' :
            'El stock se controlará como una única unidad, sin pedir talle ni color.';
    }

    async function cargarSelect(url, selectId, labelField = 'name') {
        const select = document.getElementById(selectId);

        try {
            const res = await fetch(url, {
                headers: {
                    Accept: 'application/json'
                }
            });

            const json = await res.json();
            const items = json.data ?? json;

            select.innerHTML = '<option value="">Seleccione...</option>';

            items.forEach(item => {
                const label =
                    item[labelField] ??
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
            console.error('Error cargando select:', selectId, error);

            select.innerHTML = `
            <option value="">
                Error al cargar
            </option>
        `;
        }
    }

    function configurarVistaPrevia() {
        const input = document.getElementById('images');
        const previews = document.getElementById('imagePreviews');

        input.addEventListener('change', () => {
            const files = [...(input.files ?? [])];
            if (files.length > 6) {
                alert('Podés seleccionar como máximo 6 imágenes.');
                input.value = '';
                return;
            }

            if (files.some(file => !file.type.startsWith('image/') || file.size > 2 * 1024 * 1024)) {
                alert('Todas deben ser imágenes de hasta 2 MB.');
                input.value = '';
                return;
            }

            previews.innerHTML = files.length ?
                files.map((file, index) => `
                <div class="col-4"><div class="position-relative border rounded overflow-hidden" style="height:100px">
                    <img src="${URL.createObjectURL(file)}" class="w-100 h-100" style="object-fit:cover">
                    ${index === 0 ? '<span class="badge bg-primary position-absolute top-0 start-0 m-1">Portada</span>' : ''}
                </div></div>`).join('') :
                '<div class="col-12 text-center text-muted py-4 border rounded">Sin imágenes seleccionadas</div>';
        });
    }

    function calculatePricesWithTax() {
        const taxPercentage = Number(
            document.getElementById('tax_aliquot_percentage').value || 0
        );

        const aliquotName = taxPercentage === 0 ?
            'Exento' :
            `IVA ${String(taxPercentage).replace('.', ',')}%`;

        document.getElementById('aliquot_name').value = aliquotName;

        if (!document.getElementById('auto_calculate_tax').checked) {
            if (pendingVariants.length) renderPendingVariants();
            return;
        }

        [
            ['cost_with_discount', 'cost_with_discount_with_tax_aliquot'],
            ['price_a', 'price_a_with_tax'],
            ['price_b', 'price_b_with_tax'],
            ['price_c', 'price_c_with_tax'],
            ['price_d', 'price_d_with_tax']
        ].forEach(([netId, finalId]) => {
            const netInput = document.getElementById(netId);
            const finalInput = document.getElementById(finalId);

            const netPrice = Number(netInput?.value || 0);
            const finalPrice = netPrice * (1 + taxPercentage / 100);

            if (finalInput) {
                finalInput.value = netPrice > 0 ?
                    finalPrice.toFixed(2) :
                    '';
            }
        });

        if (pendingVariants.length) renderPendingVariants();
    }

    function updateTaxCalculationMode() {
        const automatic = document.getElementById('auto_calculate_tax').checked;
        document.querySelectorAll('.tax-inclusive-price').forEach(input => {
            input.readOnly = automatic;
            input.classList.toggle('bg-light', automatic);
        });
        if (automatic) calculatePricesWithTax();
    }

    function normalizeVariantSegment(value) {
        return String(value ?? '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '');
    }

    function variantBaseCode() {
        const source = normalizeVariantSegment(
            document.getElementById('product_bar_code').value ||
            document.getElementById('product_code').value
        );

        if (!source) return '';
        if (!/^\d+$/.test(source)) return source;

        return (source.replace(/^0+/, '') || '0').padStart(4, '0');
    }

    function suggestedColorCode(label) {
        const normalized = normalizeVariantSegment(label);
        const knownCodes = {
            NEGRO: 'NGO',
            BLANCO: 'BCO',
            ROJO: 'ROJ',
            AZUL: 'AZU',
            VERDE: 'VER',
            GRIS: 'GRI',
            CELESTE: 'CEL',
            VIOLETA: 'VIO',
            ROSA: 'ROS',
            NARANJA: 'NAR',
            AMARILLO: 'AMA',
            MARRON: 'MAR',
            TURQUESA: 'TUR',
        };

        if (knownCodes[normalized]) return knownCodes[normalized];

        const words = String(label ?? '').trim().split(/\s+/).map(normalizeVariantSegment).filter(Boolean);
        if (words.length > 1) return `${words[0].slice(0, 2)}${words[1].slice(0, 1)}`.padEnd(3, '0');
        return normalized.slice(0, 3).padEnd(3, '0');
    }

    function sizeVariantCode(label) {
        const normalized = normalizeVariantSegment(label);
        return (normalized || '0').padStart(3, '0');
    }

    function escapeVariantValue(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[character]));
    }

    function updatePendingVariant(index, field, value) {
        if (!pendingVariants[index] || !['sku', 'barCode'].includes(field)) return;
        pendingVariants[index][field] = value.trim();
    }

    function renderPendingVariants() {
        const rows = document.getElementById('newVariantRows');

        if (!pendingVariants.length) {
            rows.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    Todavía no agregaste variantes.
                </td>
            </tr>
        `;
            return;
        }

        rows.innerHTML = pendingVariants.map((variant, index) => `
        <tr>
            <td>${variant.sizeLabel || 'Sin talle'}</td>
            <td>${variant.colorLabel || 'Sin color'}</td>
            <td><input class="form-control form-control-sm" value="${escapeVariantValue(variant.sku)}"
                oninput="updatePendingVariant(${index}, 'sku', this.value)" aria-label="SKU de ${escapeVariantValue(variant.sizeLabel || 'la variante')}"></td>
            <td><input class="form-control form-control-sm" name="pending_variant_bar_code_${index}"
                value="${escapeVariantValue(variant.barCode)}"
                oninput="updatePendingVariant(${index}, 'barCode', this.value)" aria-label="Código de barras de ${escapeVariantValue(variant.sizeLabel || 'la variante')}"></td>
            <td class="text-end">$ ${Number(document.getElementById('price_a_with_tax').value || 0).toLocaleString('es-AR')}</td>
            <td class="text-center">${variant.stock}</td>
            <td>${variant.image ? variant.image.name : (variant.imageUrl ? 'Imagen por URL' : '-')}</td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePendingVariant(${index})">
                    <i class="ri-delete-bin-6-line me-1"></i> Quitar
                </button>
            </td>
        </tr>
    `).join('');
    }

    function removePendingVariant(index) {
        pendingVariants.splice(index, 1);
        renderPendingVariants();
    }

    function addPendingVariant() {
        const sizes = document.getElementById('variant_size_ids');
        const color = document.getElementById('variant_color_id');
        const image = document.getElementById('variant_image').files?.[0] ?? null;
        const imageUrl = document.getElementById('variant_image_url').value.trim();

        if (imageUrl && !/^https?:\/\//i.test(imageUrl)) {
            alert('La URL de la imagen de la variante debe comenzar con http:// o https://.');
            return;
        }

        if (image && image.size > 2 * 1024 * 1024) {
            alert('La imagen de la variante no puede superar 2 MB.');
            return;
        }

        if (image && !image.type.startsWith('image/')) {
            alert('El archivo de la variante debe ser una imagen.');
            return;
        }

        calculatePricesWithTax();

        const selectedSizes = [...sizes.selectedOptions];
        const priceWithTaxValue = document.getElementById('price_a_with_tax').value;
        const priceValue = document.getElementById('price_a').value;
        const stockValue = Number(document.getElementById('variant_current_stock').value || 0);
        const hasPrice = (priceWithTaxValue && String(priceWithTaxValue).trim() !== '') || (priceValue && String(priceValue).trim() !== '');
        const hasColor = Boolean(document.getElementById('variant_color_id').value && String(document.getElementById('variant_color_id').value).trim() !== '');
        const hasSize = selectedSizes.length > 0;

        if (!hasPrice && !hasColor && !hasSize && stockValue <= 0) {
            alert('Completá al menos uno de los siguientes campos para la variante: precio, stock, talle o color.');
            return;
        }

        const baseCode = variantBaseCode();
        if (!baseCode) {
            alert('Ingresá el Código de barras o Código del producto padre antes de generar sus variantes.');
            document.getElementById('product_bar_code').focus();
            return;
        }

        const colorLabel = color.value ? color.options[color.selectedIndex].text.trim() : '';
        const colorCode = normalizeVariantSegment(document.getElementById('variant_color_code').value || (hasColor ? suggestedColorCode(colorLabel) : '000'));
        if (hasColor && colorCode.length !== 3) {
            alert('El Código color debe tener exactamente 3 caracteres.');
            document.getElementById('variant_color_code').focus();
            return;
        }

        const variantsToAdd = (selectedSizes.length ? selectedSizes : [null]).map(size => {
            const sizeLabel = size?.dataset.rawName || size?.text.trim() || '';
            const generatedCode = `${baseCode}${colorCode || '000'}${sizeVariantCode(sizeLabel)}`;

            return {
                sizeId: size?.value ?? '',
                sizeLabel,
                colorId: color.value,
                colorLabel,
                sku: generatedCode,
                barCode: generatedCode,
                stock: document.getElementById('variant_current_stock').value || 0,
                image,
                imageUrl
            };
        });

        const sameCombination = (left, right) =>
            String(left.sizeId || '') === String(right.sizeId || '') &&
            String(left.colorId || '') === String(right.colorId || '');
        const untouchedVariants = pendingVariants.filter(variant =>
            !variantsToAdd.some(candidate => sameCombination(variant, candidate))
        );
        const existingCodes = new Set(untouchedVariants
            .flatMap(variant => [variant.sku, variant.barCode])
            .filter(Boolean)
            .map(code => code.toUpperCase()));
        const generatedCodes = variantsToAdd.map(variant => variant.sku.toUpperCase());
        const repeatedCode = variantsToAdd.find(variant => existingCodes.has(variant.sku.toUpperCase()))
            || (new Set(generatedCodes).size !== generatedCodes.length ? variantsToAdd[0] : null);
        if (repeatedCode) {
            alert(`El código ${repeatedCode.sku} ya está utilizado por otra variante.`);
            return;
        }

        variantsToAdd.forEach(candidate => {
            const existingIndex = pendingVariants.findIndex(variant => sameCombination(variant, candidate));
            if (existingIndex >= 0) {
                pendingVariants[existingIndex] = candidate;
            } else {
                pendingVariants.push(candidate);
            }
        });

        [...sizes.options].forEach(option => option.selected = false);
        const sizesRoot = sizes.closest('[data-erp-multiselect]');
        sizesRoot?._multi?.refresh();
        const sizesSearch = sizesRoot?.querySelector('[data-multi-search]');
        if (sizesSearch) sizesSearch.value = '';
        const sizesOptions = sizesRoot?.querySelector('.erp-multiselect-options');
        if (sizesOptions) sizesOptions.innerHTML = '<div class="erp-remote-message">Ingresá al menos 1 carácter.</div>';
        color.value = '';
        document.getElementById('variant_color_code').value = '';
        document.getElementById('variant_current_stock').value = 0;
        document.getElementById('variant_image').value = '';
        document.getElementById('variant_image_url').value = '';
        renderPendingVariants();
    }

    async function savePendingVariants(productId) {
        const failures = [];
        const parentFinalPrice = document.getElementById('price_a_with_tax').value;

        for (let index = 0; index < pendingVariants.length; index++) {
            const variant = pendingVariants[index];
            const data = new FormData();
            data.append('product_id', productId);
            if (variant.sizeId) data.append('size_id', variant.sizeId);
            if (variant.colorId) data.append('color_id', variant.colorId);
            if (variant.sku) data.append('sku', variant.sku);
            if (variant.barCode) data.append('bar_code', variant.barCode);
            if (parentFinalPrice) data.append('price_a_with_tax', parentFinalPrice);
            data.append('current_stock', variant.stock);
            if (variant.image) data.append('image', variant.image);
            if (variant.imageUrl) data.append('image_url', variant.imageUrl);

            try {
                const response = await fetch(`${window.APP_BASE_URL}/api/product-variants`, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json'
                    },
                    body: data
                });

                if (!response.ok) failures.push(index + 1);
            } catch (error) {
                console.error(error);
                failures.push(index + 1);
            }
        }

        return failures;
    }

    document.addEventListener('DOMContentLoaded', async () => {
        document.getElementById('has_variants').addEventListener('change', updateStockMode);
        updateStockMode();
        configurarVistaPrevia();

        await Promise.all([
            cargarSelect(`${window.APP_BASE_URL}/api/brands?lookup=1`, 'brand_id'),
            cargarSelect(`${window.APP_BASE_URL}/api/product-categories?lookup=1`, 'category_id'),
            cargarSelect(`${window.APP_BASE_URL}/api/product-models?lookup=1`, 'product_model_id'),
            cargarSelect(`${window.APP_BASE_URL}/api/colors?lookup=1`, 'variant_color_id')
        ]);

        document.getElementById('variant_color_id').addEventListener('change', event => {
            const option = event.target.options[event.target.selectedIndex];
            document.getElementById('variant_color_code').value = event.target.value
                ? suggestedColorCode(option?.text ?? '')
                : '';
        });

        document.querySelectorAll('.net-price').forEach(input => {
            input.addEventListener('input', calculatePricesWithTax);
        });

        document.getElementById('price_a_with_tax')
            .addEventListener('input', renderPendingVariants);

        document
            .getElementById('tax_aliquot_percentage')
            .addEventListener('change', calculatePricesWithTax);

        document.getElementById('auto_calculate_tax')
            .addEventListener('change', updateTaxCalculationMode);

        document
            .getElementById('addVariantButton')
            .addEventListener('click', addPendingVariant);

        calculatePricesWithTax();
        updateTaxCalculationMode();
    });

    document.getElementById('form').addEventListener('submit', async e => {
        e.preventDefault();

        const formElement = e.target;
        const submitButton = document.getElementById('submitButton');
        const form = new FormData(formElement);

        const image = form.get('image');
        const maxImageSize = 2 * 1024 * 1024;
        const productImages = [...document.getElementById('images').files];

        calculatePricesWithTax();

        if (document.getElementById('has_variants').checked && pendingVariants.length) {
            const skus = pendingVariants.map(variant => variant.sku.trim()).filter(Boolean);
            const barCodes = pendingVariants.map(variant => variant.barCode.trim()).filter(Boolean);
            const hasDuplicate = values => new Set(values.map(value => value.toUpperCase())).size !== values.length;

            if (pendingVariants.some(variant => !variant.sku.trim() && !variant.barCode.trim())) {
                alert('Cada variante debe conservar al menos un SKU o Código de barras.');
                return;
            }

            if (hasDuplicate(skus) || hasDuplicate(barCodes)) {
                alert('Hay SKU o códigos de barras repetidos en la lista de variantes.');
                return;
            }
        }

        if (productImages.length > 6 || productImages.some(file => file.size > maxImageSize || !file.type.startsWith('image/'))) {
            alert('Podés cargar hasta 6 imágenes válidas de 2 MB cada una.');
            return;
        }

        if (image instanceof File && image.size > maxImageSize) {
            alert(
                'La imagen no puede superar 2 MB. ' +
                'Elegí una imagen más liviana o comprimila antes de cargarla.'
            );
            return;
        }

        if (
            image instanceof File &&
            image.size > 0 &&
            !image.type.startsWith('image/')
        ) {
            alert('El archivo seleccionado debe ser una imagen.');
            return;
        }

        if (image instanceof File && image.size === 0) {
            form.delete('image');
        }

        [
            'code',
            'bar_code',
            'reference_code',

            'currency_symbol',
            'currency_name',

            'brand_id',
            'category_id',
            'product_model_id',

            'cost_with_discount',
            'cost_with_discount_with_tax_aliquot',

            'price_a',
            'price_a_with_tax',

            'price_b',
            'price_b_with_tax',

            'price_c',
            'price_c_with_tax',

            'price_d',
            'price_d_with_tax',

            'aliquot_name',

            'weight',
            'height',
            'width',
            'length'
        ].forEach(key => {
            const value = form.get(key);

            if (
                value === '' ||
                value === null ||
                value === undefined
            ) {
                form.delete(key);
            }
        });

        submitButton.disabled = true;
        submitButton.innerHTML = `
        <span class="spinner-border spinner-border-sm me-1"></span>
        Guardando...
    `;

        try {
            const res = await fetch(`${window.APP_BASE_URL}/api/products`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json'
                },
                body: form
            });

            const text = await res.text();
            let response = null;

            try {
                response = text ? JSON.parse(text) : null;
            } catch (error) {
                response = null;
            }

            console.log('STATUS:', res.status);
            console.log('RESPUESTA:', response ?? text);

            if (res.ok && response?.id) {
                submitButton.innerHTML = `
                <span class="spinner-border spinner-border-sm me-1"></span>
                Guardando variantes...
            `;

                const usesVariants = document.getElementById('has_variants').checked;
                const failures = usesVariants ?
                    await savePendingVariants(response.id) :
                    [];

                let successMessage = pendingVariants.length && usesVariants
                    ? 'Producto y variantes guardados correctamente.'
                    : 'Producto guardado correctamente.';

                if (failures.length) {
                    alert(
                        `El producto fue guardado, pero no se pudieron guardar ` +
                        `${failures.length} variante(s). Podés completarlas desde el detalle.`
                    );
                }

                if (!failures.length) alert(successMessage);

                window.location.href = `${window.APP_BASE_URL}/demo/products/${response.id}`;
                return;
            }

            const errors = response?.errors ?
                Object.values(response.errors).flat().join('\n') :
                '';

            const serverResponse = !response && text ?
                text.replace(/<[^>]*>/g, '').trim() :
                '';

            alert([
                response?.message ?? 'Error al guardar el producto.',
                errors,
                serverResponse ?
                'Respuesta del servidor: ' + serverResponse :
                ''
            ].filter(Boolean).join('\n'));

        } catch (error) {
            console.error(error);
            alert('No se pudo conectar con el servidor.');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = `
            <i class="ri-save-line me-1"></i>
            Guardar producto
        `;
        }
    });
</script>

@endsection
