{{-- Vista: Edición de Productos. Muestra el formulario para modificar un registro de Productos. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Editar Producto</h4>
        <small class="text-muted">Actualización de datos, relaciones, precios e imagen</small>
    </div>

    <a href="{{ url('/demo/products') }}/{{ $productId }}" class="btn btn-secondary">
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
                                required
                            >
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Código / SKU</label>
                            <input
                                class="form-control"
                                name="code"
                                placeholder="Ej: REM-001"
                            >
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Código de barras</label>
                            <input
                                class="form-control"
                                name="bar_code"
                                placeholder="Ej: 779..."
                            >
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Código de referencia</label>
                            <input
                                class="form-control"
                                name="reference_code"
                                placeholder="Referencia interna"
                            >
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
                                placeholder="$"
                            >
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

                            <select
                                class="form-select"
                                name="brand_id"
                                id="brand_id"
                            >
                                <option value="">Cargando...</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Categoría</label>

                            <select
                                class="form-select"
                                name="category_id"
                                id="category_id"
                            >
                                <option value="">Cargando...</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Modelo</label>

                            <select
                                class="form-select"
                                name="product_model_id"
                                id="product_model_id"
                            >
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

                    <div style="width: 180px;">
                        <label class="form-label mb-1">Alícuota IVA</label>

                        <select
                            class="form-select form-select-sm"
                            id="tax_aliquot_percentage"
                        >
                            <option value="21">21%</option>
                            <option value="10.5">10,5%</option>
                            <option value="27">27%</option>
                            <option value="0">Exento / 0%</option>
                        </select>

                        <input
                            type="hidden"
                            name="aliquot_name"
                            id="aliquot_name"
                            value="IVA 21%"
                        >

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
                                    <th style="width: 100px;">Lista</th>
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
                                                placeholder="0,00"
                                            >
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
                                                placeholder="0,00"
                                            >
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
                                                placeholder="0,00"
                                            >
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
                                                placeholder="0,00"
                                            >
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <span class="badge bg-label-warning">
                                            Precio C
                                        </span>
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
                                                placeholder="0,00"
                                            >
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
                                                placeholder="0,00"
                                            >
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <span class="badge bg-label-secondary">
                                            Precio D
                                        </span>
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
                                                placeholder="0,00"
                                            >
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
                                                placeholder="0,00"
                                            >
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
                                    placeholder="0,00"
                                >

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
                                    placeholder="0,00"
                                >

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
                                    placeholder="0,00"
                                >

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
                                    placeholder="0,00"
                                >

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
                    <h5 class="mb-0">Galería del producto</h5>
                </div>

                <div class="card-body">

                    <div
                        id="currentImageBox"
                        class="row g-2 mb-3"
                    >
                        <div class="text-center text-muted">
                            Cargando imagen...
                        </div>
                    </div>

                    <label class="form-label">Agregar imágenes</label>

                    <input
                        class="form-control"
                        name="images[]"
                        id="images"
                        type="file"
                        accept="image/*"
                        multiple
                    >

                    <small class="text-muted d-block mt-2">
                        Imagen opcional. Tamaño máximo: 2 MB.
                    </small>

                    @include('components.product-image-url-manager')

                    <div id="newImagePreviews" class="row g-2 mt-2"></div>

                </div>
            </div>

            <div class="card">
                <div class="card-body">

                    <div class="d-grid gap-2">
                        <button
                            id="submitButton"
                            class="btn btn-success"
                            type="submit"
                        >
                            <i class="ri-save-line me-1"></i>
                            Guardar cambios
                        </button>

                        <a
                            href="{{ url('/demo/products') }}/{{ $productId }}"
                            class="btn btn-outline-secondary"
                        >
                            Cancelar
                        </a>
                    </div>

                </div>
            </div>

        </div>

    </div>

</form>

<script>
const productId = "{{ $productId }}";
let product = null;

async function cargarSelect(url, selectId, selectedValue = null) {
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
            const selected =
                Number(item.id) === Number(selectedValue)
                    ? 'selected'
                    : '';

            const label =
                item.name ??
                item.description ??
                `ID ${item.id}`;

            select.innerHTML += `
                <option value="${item.id}" ${selected}>
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

function setValue(name, value) {
    const input = document.querySelector(`[name="${name}"]`);

    if (input) {
        input.value = value ?? '';
    }
}

function getTaxPercentage(aliquotName) {
    const text = String(aliquotName ?? '').toLowerCase();

    if (text.includes('10,5') || text.includes('10.5')) {
        return 10.5;
    }

    if (text.includes('27')) {
        return 27;
    }

    if (
        text.includes('exento') ||
        text.includes('0%') ||
        text.includes('no gravado')
    ) {
        return 0;
    }

    return 21;
}

function setTaxPercentageFromProduct() {
    const taxPercentage = getTaxPercentage(product?.aliquot_name);

    document.getElementById('tax_aliquot_percentage').value =
        String(taxPercentage);

    document.getElementById('aliquot_name').value =
        taxPercentage === 0
            ? 'Exento'
            : `IVA ${String(taxPercentage).replace('.', ',')}%`;
}

function calculatePricesWithTax() {
    const taxPercentage = Number(
        document.getElementById('tax_aliquot_percentage').value || 0
    );

    document.getElementById('aliquot_name').value =
        taxPercentage === 0
            ? 'Exento'
            : `IVA ${String(taxPercentage).replace('.', ',')}%`;

    if (!document.getElementById('auto_calculate_tax').checked) return;

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
            finalInput.value =
                netPrice > 0
                    ? finalPrice.toFixed(2)
                    : '';
        }
    });
}

function renderCurrentImage() {
    const box = document.getElementById('currentImageBox');
    const images = product.images ?? [];

    box.innerHTML = images.length ? images.map((image, index) => `
        <div class="col-6">
            <div class="position-relative border rounded overflow-hidden" style="height:130px">
                <img src="${image.full_url}" class="w-100 h-100" style="object-fit:cover" alt="${product.name}">
                ${index === 0 ? '<span class="badge bg-primary position-absolute top-0 start-0 m-1">Portada</span>' : ''}
                <label class="btn btn-sm btn-danger position-absolute bottom-0 end-0 m-1">
                    <input class="d-none remove-product-image" type="checkbox" name="remove_image_ids[]" value="${image.id}">
                    <i class="ri-delete-bin-line"></i> Quitar
                </label>
            </div>
        </div>`).join('') : '<div class="col-12 text-center text-muted py-4 border rounded">Este producto no tiene imágenes</div>';

    box.querySelectorAll('.remove-product-image').forEach(input => input.addEventListener('change', () => {
        input.closest('.col-6').classList.toggle('opacity-50', input.checked);
    }));
}

const productPriceFields = [
    'cost_with_discount',
    'cost_with_discount_with_tax_aliquot',
    'price_a', 'price_a_with_tax',
    'price_b', 'price_b_with_tax',
    'price_c', 'price_c_with_tax',
    'price_d', 'price_d_with_tax'
];

function setPriceValue(name, value) {
    const input = document.querySelector(`[name="${name}"]`);
    if (!input) return;

    const original = value === null || value === undefined ? '' : String(value);
    const numeric = Number(original);
    const displayed = original !== '' && Number.isFinite(numeric)
        ? String(Math.round((numeric + Number.EPSILON) * 100) / 100)
        : '';

    input.value = displayed;
    input.dataset.originalPrice = original;
    input.dataset.initialDisplayedPrice = displayed;
}

function configurarVistaPreviaNuevaImagen() {
    const input = document.getElementById('images');
    const previewBox = document.getElementById('newImagePreviews');

    input.addEventListener('change', () => {
        const files = [...(input.files ?? [])];
        const removed = document.querySelectorAll('.remove-product-image:checked').length;
        const remaining = (product.images ?? []).length - removed;

        if (remaining + files.length > 6) {
            alert(`Solo podés agregar ${Math.max(0, 6 - remaining)} imagen(es) más.`);
            input.value = '';
            return;
        }

        if (files.some(file => !file.type.startsWith('image/') || file.size > 2 * 1024 * 1024)) {
            alert('Todas deben ser imágenes de hasta 2 MB.');
            input.value = '';
            return;
        }

        previewBox.innerHTML = files.map(file => `<div class="col-4"><img src="${URL.createObjectURL(file)}" class="w-100 rounded border" style="height:90px;object-fit:cover"></div>`).join('');
    });
}

function updateTaxCalculationMode() {
    const automatic = document.getElementById('auto_calculate_tax').checked;
    document.querySelectorAll('.tax-inclusive-price').forEach(input => {
        input.readOnly = automatic;
        input.classList.toggle('bg-light', automatic);
    });
    if (automatic) calculatePricesWithTax();
}

async function cargarProducto() {
    const res = await fetch(`${window.APP_BASE_URL}/api/products/${productId}`, {
        headers: {
            Accept: 'application/json'
        }
    });

    if (!res.ok) {
        throw new Error('No se pudo cargar el producto.');
    }

    product = await res.json();

    setValue('name', product.name);
    setValue('code', product.code);
    setValue('bar_code', product.bar_code);
    setValue('reference_code', product.reference_code);

    setValue('currency_name', product.currency_name ?? 'Pesos');
    setValue('currency_symbol', product.currency_symbol ?? '$');

    setValue('weight', product.weight);
    setValue('height', product.height);
    setValue('width', product.width);
    setValue('length', product.length);

    setPriceValue('cost_with_discount', product.cost_with_discount);
    setPriceValue('cost_with_discount_with_tax_aliquot', product.cost_with_discount_with_tax_aliquot);

    setPriceValue('price_a', product.price_a);
    setPriceValue('price_a_with_tax', product.price_a_with_tax);

    setPriceValue('price_b', product.price_b);
    setPriceValue('price_b_with_tax', product.price_b_with_tax);

    setPriceValue('price_c', product.price_c);
    setPriceValue('price_c_with_tax', product.price_c_with_tax);

    setPriceValue('price_d', product.price_d);
    setPriceValue('price_d_with_tax', product.price_d_with_tax);

    setTaxPercentageFromProduct();
    renderCurrentImage();

    await Promise.all([
        cargarSelect(
            `${window.APP_BASE_URL}/api/brands?lookup=1`,
            'brand_id',
            product.brand_id
        ),
        cargarSelect(
            `${window.APP_BASE_URL}/api/product-categories?lookup=1`,
            'category_id',
            product.category_id
        ),
        cargarSelect(
            `${window.APP_BASE_URL}/api/product-models?lookup=1`,
            'product_model_id',
            product.product_model_id
        )
    ]);

    calculatePricesWithTax();
}

document.addEventListener('DOMContentLoaded', async () => {
    configurarVistaPreviaNuevaImagen();

    document.querySelectorAll('.net-price').forEach(input => {
        input.addEventListener('input', calculatePricesWithTax);
    });

    document
        .getElementById('tax_aliquot_percentage')
        .addEventListener('change', calculatePricesWithTax);

    document.getElementById('auto_calculate_tax')
        .addEventListener('change', updateTaxCalculationMode);

    updateTaxCalculationMode();

    try {
        await cargarProducto();
    } catch (error) {
        console.error(error);

        alert(
            error.message ??
            'No se pudo cargar el producto.'
        );
    }
});

document
    .getElementById('form')
    .addEventListener('submit', async event => {
        event.preventDefault();

        const formElement = event.target;
        const submitButton = document.getElementById('submitButton');
        const form = new FormData(formElement);

        productPriceFields.forEach(name => {
            const input = formElement.elements.namedItem(name);
            if (!input || input.value !== input.dataset.initialDisplayedPrice) return;
            const original = input.dataset.originalPrice;
            if (original === '') form.delete(name);
            else form.set(name, original);
        });

        const image = form.get('image');
        const maxImageSize = 2 * 1024 * 1024;
        const newImages = [...document.getElementById('images').files];

        if (newImages.some(file => file.size > maxImageSize || !file.type.startsWith('image/'))) {
            alert('Todas las imágenes deben ser válidas y pesar hasta 2 MB.');
            return;
        }

        calculatePricesWithTax();

        if (image instanceof File && image.size > maxImageSize) {
            alert(
                'La imagen no puede superar 2 MB. ' +
                'Elegí una imagen más liviana o comprimila.'
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

        form.append('_method', 'PUT');

        [
            'code',
            'bar_code',
            'reference_code',

            'currency_name',
            'currency_symbol',

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
            const res = await fetch(`${window.APP_BASE_URL}/api/products/${productId}`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json'
                },
                body: form
            });

            const text = await res.text();
            let response = null;

            try {
                response = text
                    ? JSON.parse(text)
                    : null;
            } catch (error) {
                response = null;
            }

            if (res.ok) {
                window.location.href =
                    `${window.APP_BASE_URL}/demo/products/${productId}`;

                return;
            }

            const errors = response?.errors
                ? Object.values(response.errors)
                    .flat()
                    .join('\n')
                : '';

            const serverResponse =
                !response && text
                    ? text
                        .replace(/<[^>]*>/g, '')
                        .trim()
                    : '';

            alert([
                response?.message ??
                    'Error al editar el producto.',
                errors,
                serverResponse
                    ? 'Respuesta del servidor: ' + serverResponse
                    : ''
            ].filter(Boolean).join('\n'));

        } catch (error) {
            console.error(error);

            alert(
                'No se pudo conectar con el servidor.'
            );
        } finally {
            submitButton.disabled = false;

            submitButton.innerHTML = `
                <i class="ri-save-line me-1"></i>
                Guardar cambios
            `;
        }
    });
</script>

@endsection
