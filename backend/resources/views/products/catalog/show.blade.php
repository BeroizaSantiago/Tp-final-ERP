{{-- Vista: Detalle de Productos. Muestra la información completa de un registro de Productos. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <x-action-button action="back" :href="url('/demo/products')" :icon-only="true" />
        <div>
            <h4 class="mb-1">Producto</h4>
            <small class="text-muted">Configuración, variantes, precios y stock</small>
            <div id="woocommerceSyncStatus" class="mt-2 d-none"></div>
        </div>
    </div>

    <div class="d-flex flex-column align-items-end gap-2">
        <div class="d-flex gap-2">
            <button type="button" id="woocommercePublishButton" class="btn btn-success rounded-pill btn-icon d-none" title="Actualizar WooCommerce" aria-label="Actualizar WooCommerce">
                <i class="icon-base ri ri-refresh-line"></i><span class="visually-hidden">Publicar en WooCommerce</span>
            </button>
            <button type="button" id="woocommercePullButton" class="btn btn-primary rounded-pill btn-icon d-none" title="Traer desde WooCommerce" aria-label="Traer desde WooCommerce">
                <i class="icon-base ri ri-download-line"></i><span class="visually-hidden">Traer desde WooCommerce</span>
            </button>
            <button type="button" id="productStatusButton" class="btn btn-danger rounded-pill btn-icon d-none" data-erp-action-ignore="true" title="Dar de baja" aria-label="Dar de baja">
                <i class="icon-base ri ri-forbid-line"></i><span class="visually-hidden">Dar de baja</span>
            </button>
            <x-action-button action="edit" :href="url('/demo/products/'.$productId.'/edit')" :icon-only="true" />
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#integrationHistoryModal" data-erp-action-ignore="true">
            <i class="icon-base ri ri-history-line me-1"></i>Ver historial
        </button>
    </div>
</div>

<div id="productInfo">
    <div class="card mb-4">
        <div class="card-body text-center text-muted py-5">
            Cargando producto...
        </div>
    </div>
</div>

<div class="modal fade" id="integrationHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title">Historial de integración</h5><small class="text-muted">Modificaciones intercambiadas entre el ERP y WooCommerce</small></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Fecha</th><th>Dirección</th><th>Acción</th><th>Cambios</th><th>Usuario</th><th>Estado</th></tr></thead>
                        <tbody id="integrationHistoryRows"><tr><td colspan="6" class="text-center text-muted py-4">Sin movimientos registrados.</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <small id="integrationHistoryInfo" class="text-muted"></small>
                <div class="btn-group btn-group-sm">
                    <button id="historyPrevious" type="button" class="btn btn-outline-secondary">Anterior</button>
                    <button id="historyNext" type="button" class="btn btn-outline-secondary">Siguiente</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-1">Agregar variante</h5>
        <small class="text-muted">Seleccioná uno o varios talles; el precio se toma del producto padre.</small>
    </div>

    <div class="card-body">
        <form id="variantForm" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="{{ $productId }}">

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
                    <div class="alert alert-light border py-2 px-3 mb-0 small">
                        Si una combinación de talle y color ya existe, se actualizará esa variante en lugar de crear otra.
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Imagen desde archivo</label>
                            <input class="form-control" id="variantImage" type="file" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Imagen mediante URL</label>
                            <div class="input-group">
                                <input class="form-control" id="variantImageUrl" type="url" maxlength="255" placeholder="https://...">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Vista previa</label>
                    <div class="border rounded d-flex align-items-center justify-content-center overflow-hidden" style="height: 74px;">
                        <span id="variantImagePlaceholder" class="text-muted small">Sin imagen</span>
                        <img id="variantImagePreview" class="d-none w-100 h-100" style="object-fit: contain;" alt="Vista previa">
                    </div>
                </div>

                <div class="col-lg-3 d-grid align-self-end">
                    <button id="variantSubmitButton" type="submit" class="btn btn-outline-success">
                        <i class="ri-add-line me-1"></i>
                        Agregar o actualizar variantes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1">Variantes</h5>
            <small class="text-muted">Talles, colores, precios y stock por variante</small>
        </div>

        <span id="variantCount" class="badge bg-label-primary">
            0 variantes
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Variante</th>
                    <th>SKU</th>
                    <th>Cód. barras</th>
                    <th class="text-end">Precio sin IVA</th>
                    <th class="text-end">Precio con IVA</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center">Activo</th>
                </tr>
            </thead>

            <tbody id="variantRows">
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        Cargando variantes...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>



<div id="stockInfo"></div>

<script>
const productId = "{{ $productId }}";

let product = null;
let productTaxPercentage = 21;

function money(value) {
    return Number(value ?? 0).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS'
    });
}

function formatValue(value, suffix = '') {
    if (
        value === null ||
        value === undefined ||
        value === ''
    ) {
        return '-';
    }

    return `${value}${suffix}`;
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

async function cargarSelect(url, id) {
    const select = document.getElementById(id);

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
            select.innerHTML += `
                <option value="${item.id}">
                    ${item.name ?? `ID ${item.id}`}
                </option>
            `;
        });

    } catch (error) {
        console.error(error);

        select.innerHTML = `
            <option value="">
                Error al cargar
            </option>
        `;
    }
}

function normalizeVariantSegment(value, length, padCharacter = '0') {
    const normalized = String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase()
        .replace(/[^A-Z0-9]/g, '');

    return normalized.slice(-length).padStart(length, padCharacter);
}

function variantBaseCode() {
    return normalizeVariantSegment(product?.bar_code || product?.code || productId, 4);
}

function suggestedColorCode(name) {
    const normalized = String(name ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase();
    const known = {
        NEGRO: 'NGO', BLANCO: 'BCO', ROJO: 'ROJ', AZUL: 'AZU', VERDE: 'VDE',
        AMARILLO: 'AMA', GRIS: 'GRS', ROSA: 'RSA', CELESTE: 'CEL', NARANJA: 'NJA'
    };
    return known[normalized] || normalizeVariantSegment(normalized, 3, 'X');
}

function generatedVariantCode(sizeName) {
    const colorCode = normalizeVariantSegment(document.getElementById('variant_color_code').value, 3, 'X');
    const sizeCode = normalizeVariantSegment(sizeName || '0', 3);
    return `${variantBaseCode()}${colorCode}${sizeCode}`;
}

function configurarVistaPreviaVariante() {
    const input = document.getElementById('variantImage');
    const urlInput = document.getElementById('variantImageUrl');
    const preview = document.getElementById('variantImagePreview');
    const placeholder = document.getElementById('variantImagePlaceholder');

    const showPreview = url => {
        preview.src = url || '';
        preview.classList.toggle('d-none', !url);
        placeholder.classList.toggle('d-none', Boolean(url));
    };

    const previewUrl = () => {
        if (input.files?.length) return;
        const url = urlInput.value.trim();
        showPreview(/^https?:\/\//i.test(url) ? url : '');
    };

    input.addEventListener('change', () => {
        const file = input.files?.[0];

        if (!file) {
            previewUrl();
            return;
        }

        if (!file.type.startsWith('image/')) {
            alert('El archivo seleccionado debe ser una imagen.');
            input.value = '';
            return;
        }

        const reader = new FileReader();

        reader.onload = event => {
            showPreview(event.target.result);
        };

        reader.readAsDataURL(file);
    });

    urlInput.addEventListener('input', previewUrl);
    urlInput.addEventListener('change', previewUrl);
    preview.addEventListener('error', () => {
        if (!input.files?.length) showPreview('');
    });

}

function renderProductInfo() {
    const galleryImages = (product.images ?? []).map(image => image.full_url);
    if (!galleryImages.length && product.image_full_url) galleryImages.push(product.image_full_url);

    const galleryHtml = galleryImages.length ? `
        <div id="productGallery" class="carousel slide w-100 h-100" data-bs-ride="false">
            <div class="carousel-inner h-100">
                ${galleryImages.map((url, index) => `
                    <div class="carousel-item h-100 ${index === 0 ? 'active' : ''}">
                        <img src="${url}" class="d-block w-100 h-100" style="object-fit:contain" alt="${product.name}">
                    </div>`).join('')}
            </div>
            ${galleryImages.length > 1 ? `
                <button class="carousel-control-prev" type="button" data-bs-target="#productGallery" data-bs-slide="prev"><span class="carousel-control-prev-icon bg-dark rounded-circle"></span></button>
                <button class="carousel-control-next" type="button" data-bs-target="#productGallery" data-bs-slide="next"><span class="carousel-control-next-icon bg-dark rounded-circle"></span></button>
                <div class="carousel-indicators mb-1">${galleryImages.map((_, index) => `<button type="button" data-bs-target="#productGallery" data-bs-slide-to="${index}" class="${index === 0 ? 'active' : ''}"></button>`).join('')}</div>` : ''}
        </div>` : '<div class="text-center text-muted"><i class="ri-image-line" style="font-size:56px"></i><div class="mt-2">Sin imágenes</div></div>';

    productTaxPercentage = getTaxPercentage(product.aliquot_name);

    document.getElementById('productInfo').innerHTML = `
        <div class="row">

            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">

                        <div
                            class="border rounded d-flex justify-content-center align-items-center mb-3"
                            style="height:280px; background:#f8f9fa; overflow:hidden;"
                        >
                            ${galleryHtml}
                        </div>

                        <h4 class="mb-1">${product.name ?? '-'}</h4>

                        <div class="text-muted mb-3">
                            ${product.code ?? 'Sin código'}
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-label-primary">
                                ${product.category?.name ?? product.category ?? 'Sin categoría'}
                            </span>

                            <span class="badge bg-label-secondary">
                                ${product.brand?.name ?? product.brand ?? 'Sin marca'}
                            </span>

                            <span class="badge bg-label-info">
                                ${product.model?.name ?? product.model ?? 'Sin modelo'}
                            </span>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-4">

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Información general</h5>
                    </div>

                    <div class="card-body">
                        <div class="row">

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">ID</small>
                                <div class="fw-semibold">${product.id}</div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Código</small>
                                <div class="fw-semibold">${product.code ?? '-'}</div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Código de barras</small>
                                <div class="fw-semibold">${product.bar_code ?? '-'}</div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Referencia</small>
                                <div class="fw-semibold">${product.reference_code ?? '-'}</div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Marca</small>
                                <div class="fw-semibold">
                                    ${product.brand?.name ?? product.brand ?? '-'}
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Modelo</small>
                                <div class="fw-semibold">
                                    ${product.model?.name ?? product.model ?? '-'}
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Categoría</small>
                                <div class="fw-semibold">
                                    ${product.category?.name ?? product.category ?? '-'}
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Alícuota</small>
                                <div class="fw-semibold">
                                    ${product.aliquot_name ?? `IVA ${productTaxPercentage}%`}
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Moneda</small>
                                <div class="fw-semibold">
                                    ${product.currency_name ?? 'Pesos'}
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Precios y stock</h5>
                    </div>

                    <div class="card-body">
                        <div class="row">

                            <div class="col-12 mb-3">
                                <h6 class="text-uppercase text-muted mb-3">Costo</h6>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Costo sin IVA</small>
                                <h4 class="mb-0">${money(product.cost_with_discount)}</h4>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Costo con IVA</small>
                                <h4 class="mb-0">${money(product.cost_with_discount_with_tax_aliquot)}</h4>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Alícuota aplicada</small>
                                <h4 class="mb-0">${product.aliquot_name ?? `IVA ${productTaxPercentage}%`}</h4>
                            </div>

                            <div class="col-12"><hr><h6 class="text-uppercase text-muted mb-3">Precios de Lista</h6></div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Precio A sin IVA</small>
                                <h4 class="mb-0">${money(product.price_a)}</h4>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Precio A con IVA</small>
                                <h4 class="mb-0 text-success">${money(product.price_a_with_tax)}</h4>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Markup A</small>
                                <h4 class="mb-0">${product.markup_a ?? 0}%</h4>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Precio B sin IVA</small>
                                <h4 class="mb-0">${money(product.price_b)}</h4>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Precio B con IVA</small>
                                <h4 class="mb-0 text-info">${money(product.price_b_with_tax)}</h4>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Markup B</small>
                                <h4 class="mb-0">${product.markup_b ?? 0}%</h4>
                            </div>

                            <div class="col-12"><hr><h6 class="text-uppercase text-muted mb-3">Existencias</h6></div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Stock del producto</small>
                                <h4 class="mb-0">
                                    ${product.current_stock ?? 0}
                                </h4>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Stock disponible</small>
                                <h4 class="mb-0">
                                    ${product.available_stock ?? product.current_stock ?? 0}
                                </h4>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Estado</small>
                                <div class="mt-1">
                                    ${product.is_active === false
                                        ? `<span class="badge bg-label-danger">Inactivo</span>`
                                        : `<span class="badge bg-label-success">Activo</span>`
                                    }
                                </div>
                            </div>

                        </div>
                    </div>
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
                        <small class="text-muted">Peso</small>
                        <div class="fw-semibold">
                            ${formatValue(product.weight, ' kg')}
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <small class="text-muted">Altura</small>
                        <div class="fw-semibold">
                            ${formatValue(product.height, ' cm')}
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <small class="text-muted">Ancho</small>
                        <div class="fw-semibold">
                            ${formatValue(product.width, ' cm')}
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <small class="text-muted">Largo</small>
                        <div class="fw-semibold">
                            ${formatValue(product.length, ' cm')}
                        </div>
                    </div>

                </div>
            </div>
        </div>
    `;
}

function renderProductStatus() {
    const button = document.getElementById('productStatusButton');
    const active = product.is_active !== false;
    button.classList.remove('d-none', 'btn-danger', 'btn-success');
    button.classList.add(active ? 'btn-danger' : 'btn-success');
    button.innerHTML = active
        ? '<i class="icon-base ri ri-forbid-line"></i><span class="visually-hidden">Dar de baja</span>'
        : '<i class="icon-base ri ri-checkbox-circle-line"></i><span class="visually-hidden">Habilitar</span>';
    button.title = active ? 'Dar de baja' : 'Habilitar';
    document.querySelectorAll('#variantForm input, #variantForm select, #variantForm button').forEach(control => {
        control.disabled = !active;
    });
}

document.getElementById('productStatusButton').addEventListener('click', async () => {
    const active = product.is_active !== false;
    const action = active ? 'dar de baja' : 'habilitar';
    const confirmed = await window.erpConfirm(
        `¿Confirmás que querés ${action} este producto?`,
        {
            title: active ? 'Inhabilitar producto' : 'Habilitar producto',
            icon: active ? 'warning' : 'question',
            confirmButtonText: active ? 'Sí, inhabilitar' : 'Sí, habilitar'
        }
    );
    if (!confirmed) return;

    const response = await fetch(`${window.APP_BASE_URL}/api/products/${productId}/status`, {
        method: 'PATCH',
        headers: {'Content-Type': 'application/json', Accept: 'application/json'},
        body: JSON.stringify({is_active: !active})
    });
    const data = await response.json();
    if (!response.ok) {
        await Swal.fire({
            icon: 'error',
            title: 'No se pudo actualizar',
            text: data.message || 'No se pudo modificar el estado del producto.'
        });
        return;
    }
    product.is_active = data.product.is_active;
    renderProductStatus();
    renderProductInfo();
    await Swal.fire({
        icon: 'success',
        title: active ? 'Producto inhabilitado' : 'Producto habilitado',
        text: data.message,
        confirmButtonText: 'Aceptar'
    });
});

function renderVariants() {
    const variants = product.variants ?? [];

    document.getElementById('variantCount').innerText =
        `${variants.length} ${variants.length === 1 ? 'variante' : 'variantes'}`;

    if (!variants.length) {
        document.getElementById('variantRows').innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    No hay variantes cargadas
                </td>
            </tr>
        `;
        return;
    }

    document.getElementById('variantRows').innerHTML = variants.map(variant => {
        const imageUrl =
            variant.image_full_url ??
            (
                variant.image_url
                    ? `${window.APP_BASE_URL}/media/${variant.image_url}`
                    : null
            );

        // Las variantes heredan los precios definidos en el producto padre.
        // En modo manual el precio final no necesariamente surge de aplicar
        // la alícuota al neto, por lo que no debe reconstruirse dividiendo IVA.
        const variantNetPrice = variant.price_a ?? product.price_a ?? 0;

        return `
            <tr>
                <td>
                    ${imageUrl
                        ? `
                            <img
                                src="${imageUrl}"
                                alt="${variant.sku ?? product.name ?? 'Variante'}"
                                style="width:54px; height:54px; object-fit:cover; border-radius:6px;"
                            >
                        `
                        : `
                            <span class="avatar-initial rounded bg-label-secondary">
                                <i class="ri-image-line"></i>
                            </span>
                        `
                    }
                </td>

                <td>
                    <strong>
                        ${variant.size?.name ?? 'Sin talle'}
                        /
                        ${variant.color?.name ?? 'Sin color'}
                    </strong>
                </td>

                <td>${variant.sku ?? '-'}</td>

                <td>${variant.bar_code ?? '-'}</td>

                <td class="text-end">
                    ${money(variantNetPrice)}
                </td>

                <td class="text-end">
                    <strong>${money(variant.price_a_with_tax)}</strong>
                </td>

                <td class="text-center">
                    <span class="badge ${
                        Number(variant.current_stock ?? 0) > 0
                            ? 'bg-label-success'
                            : 'bg-label-danger'
                    }">
                        ${variant.current_stock ?? 0}
                    </span>
                </td>

                <td class="text-center">
                    ${variant.is_active === false
                        ? `<span class="badge bg-label-danger">No</span>`
                        : `<span class="badge bg-label-success">Sí</span>`
                    }
                </td>
            </tr>
        `;
    }).join('');
}

function renderStockInfo() {
    const variants = product.variants ?? [];

    const totalVariantStock = variants.reduce(
        (total, variant) => total + Number(variant.current_stock ?? 0),
        0
    );

    const variantsWithStock = variants.filter(
        variant => Number(variant.current_stock ?? 0) > 0
    ).length;

    document.getElementById('stockInfo').innerHTML = `
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Resumen de stock</h5>
            </div>

            <div class="card-body">
                <div class="row">

                    <div class="col-md-3 mb-3">
                        <small class="text-muted">Stock producto</small>
                        <h4 class="mb-0">
                            ${product.current_stock ?? 0}
                        </h4>
                    </div>

                    <div class="col-md-3 mb-3">
                        <small class="text-muted">Stock total variantes</small>
                        <h4 class="mb-0">
                            ${totalVariantStock}
                        </h4>
                    </div>

                    <div class="col-md-3 mb-3">
                        <small class="text-muted">Cantidad de variantes</small>
                        <h4 class="mb-0">
                            ${variants.length}
                        </h4>
                    </div>

                    <div class="col-md-3 mb-3">
                        <small class="text-muted">Variantes con stock</small>
                        <h4 class="mb-0">
                            ${variantsWithStock}
                        </h4>
                    </div>

                </div>
            </div>
        </div>
    `;
}

async function cargarProducto() {
    const res = await fetch(`${window.APP_BASE_URL}/api/products/${productId}`, {
        headers: {
            Accept: 'application/json'
        }
    });

    if (!res.ok) {
        document.getElementById('productInfo').innerHTML = `
            <div class="alert alert-danger">
                No se pudo cargar el producto.
            </div>
        `;
        return;
    }

    product = await res.json();

    renderProductInfo();
    renderProductStatus();
    renderVariants();
    renderStockInfo();
    renderWooCommerceStatus();
}

let integrationHistoryPage = 1;
let integrationHistoryLastPage = 1;

const integrationActionLabels = {
    create: 'Creación', update: 'Actualización', delete: 'Eliminación',
    publish: 'Publicación', sync: 'Sincronización', stock_update: 'Actualización de stock'
};
const integrationFieldLabels = {
    id: 'ID de WooCommerce', name: 'Nombre', status: 'Estado', sku: 'SKU',
    bar_code: 'Código de barras', regular_price: 'Precio',
    price_a_with_tax: 'Precio final A', price_b_with_tax: 'Precio final B',
    price_c_with_tax: 'Precio final C', price_d_with_tax: 'Precio final D',
    stock_quantity: 'Cantidad en stock', current_stock: 'Stock actual',
    available_stock: 'Stock disponible', weight: 'Peso', height: 'Alto',
    width: 'Ancho', length: 'Largo', dimensions: 'Dimensiones',
    category_id: 'Categoría', category: 'Categoría', categories: 'Categorías',
    image: 'Imagen', image_url: 'Imagen', images: 'Imágenes', attributes: 'Atributos',
    is_web_enabled: 'Publicado en la web', is_active: 'Estado activo',
    can_move_stock: 'Mueve stock', allows_negative_stock: 'Permite stock negativo',
    web_title: 'Título web', description: 'Descripción',
    web_description: 'Descripción web', web_short_description: 'Descripción corta web'
};
const integrationActionLabel = value => integrationActionLabels[value] || String(value || '-').replaceAll('_', ' ');
const integrationFieldLabel = value => integrationFieldLabels[value] || String(value || '-').replaceAll('_', ' ');

async function loadIntegrationHistory(page = 1) {
    const rows = document.getElementById('integrationHistoryRows');
    rows.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Cargando historial...</td></tr>';
    const response = await fetch(`${window.APP_BASE_URL}/api/integrations/woocommerce/products/${productId}/history?page=${page}`, { headers: { Accept: 'application/json' } });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        rows.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">No se pudo cargar el historial.</td></tr>';
        return;
    }
    integrationHistoryPage = data.current_page;
    integrationHistoryLastPage = data.last_page;
    rows.innerHTML = data.data.length ? data.data.map(item => {
        const direction = item.direction === 'erp_to_woocommerce' ? 'ERP → WooCommerce' : 'WooCommerce → ERP';
        const fields = (item.changed_fields || []).map(field => `<span class="badge bg-label-secondary me-1 mb-1">${escapeProductText(integrationFieldLabel(field))}</span>`).join('') || '-';
        const status = item.status === 'success' ? 'success' : 'danger';
        return `<tr><td>${new Date(item.created_at).toLocaleString('es-AR')}</td><td>${direction}${item.variant ? `<br><small class="text-muted">${escapeProductText(item.variant.sku || '')}</small>` : ''}</td><td>${escapeProductText(integrationActionLabel(item.action))}</td><td>${fields}${item.error ? `<div class="small text-danger">${escapeProductText(item.error)}</div>` : ''}</td><td>${escapeProductText(item.user?.name || 'Automático')}</td><td><span class="badge bg-label-${status}">${item.status === 'success' ? 'Correcto' : 'Error'}</span></td></tr>`;
    }).join('') : '<tr><td colspan="6" class="text-center text-muted py-4">Sin movimientos registrados.</td></tr>';
    document.getElementById('integrationHistoryInfo').textContent = data.total ? `Página ${data.current_page} de ${data.last_page} · ${data.total} registros` : '';
    document.getElementById('historyPrevious').disabled = data.current_page <= 1;
    document.getElementById('historyNext').disabled = data.current_page >= data.last_page;
}

document.getElementById('historyPrevious').addEventListener('click', () => loadIntegrationHistory(integrationHistoryPage - 1));
document.getElementById('historyNext').addEventListener('click', () => loadIntegrationHistory(integrationHistoryPage + 1));
document.getElementById('integrationHistoryModal').addEventListener('show.bs.modal', () => loadIntegrationHistory(1));

function escapeProductText(value) {
    const element = document.createElement('div');
    element.textContent = String(value ?? '');
    return element.innerHTML;
}

function renderWooCommerceStatus() {
    const button = document.getElementById('woocommercePublishButton');
    const status = document.getElementById('woocommerceSyncStatus');
    const canRetry = product.woocommerce_sync_status === 'error';
    const isLinked = Boolean(product.woocommerce_id);
    button.classList.toggle('d-none', !canRetry && !isLinked);
    document.getElementById('woocommercePullButton').classList.toggle('d-none', !isLinked);
    button.querySelector('span').textContent = isLinked ? 'Actualizar WooCommerce' : 'Reintentar WooCommerce';
    button.title = product.woocommerce_sync_error || '';

    if (product.woocommerce_sync_status === 'synced') {
        status.className = 'mt-2';
        status.innerHTML = `<span class="badge bg-label-success"><i class="ri-checkbox-circle-line me-1"></i>Publicado en WooCommerce · ID ${product.woocommerce_id}</span>`;
    } else if (product.woocommerce_sync_status === 'error') {
        status.className = 'mt-2';
        status.innerHTML = `<span class="badge bg-label-danger"><i class="ri-error-warning-line me-1"></i>No publicado en WooCommerce</span> <small class="text-danger ms-1">${escapeProductText(product.woocommerce_sync_error || '')}</small>`;
    } else {
        status.className = 'mt-2 d-none';
        status.innerHTML = '';
    }
}

document.getElementById('woocommercePullButton').addEventListener('click', async () => {
    const button = document.getElementById('woocommercePullButton');
    const original = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    try {
        const response = await fetch(`${window.APP_BASE_URL}/api/integrations/woocommerce/products/${productId}/pull`, {
            method: 'POST', headers: { Accept: 'application/json' }
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error([data.message, data.error].filter(Boolean).join(' '));
        await Swal.fire({ icon: 'success', title: 'Producto actualizado', text: data.message, confirmButtonText: 'Aceptar' });
        await cargarProducto();
    } catch (error) {
        await Swal.fire({ icon: 'error', title: 'No se pudo importar', text: error.message, confirmButtonText: 'Aceptar' });
    } finally {
        button.disabled = false;
        button.innerHTML = original;
    }
});

document.getElementById('woocommercePublishButton').addEventListener('click', async () => {
    const button = document.getElementById('woocommercePublishButton');
    button.disabled = true;
    const original = button.innerHTML;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sincronizando...';
    try {
        const response = await fetch(`${window.APP_BASE_URL}/api/integrations/woocommerce/products/${productId}/publish`, {
            method: 'POST', headers: { Accept: 'application/json' }
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error([data.message, data.error].filter(Boolean).join(' '));
        await Swal.fire({icon:'success', title:'WooCommerce actualizado', text:data.message, confirmButtonText:'Aceptar'});
        await cargarProducto();
    } catch (error) {
        await Swal.fire({icon:'error', title:'No se pudo publicar', text:error.message, confirmButtonText:'Aceptar'});
    } finally {
        button.disabled = false;
        button.innerHTML = original;
        if (product) renderWooCommerceStatus();
    }
});

document.addEventListener('DOMContentLoaded', async () => {
    configurarVistaPreviaVariante();

    await Promise.all([
        cargarSelect(`${window.APP_BASE_URL}/api/colors?lookup=1`, 'variant_color_id'),
        cargarProducto()
    ]);

    document.getElementById('variant_color_id').addEventListener('change', event => {
        const selected = event.target.options[event.target.selectedIndex];
        document.getElementById('variant_color_code').value = selected?.value
            ? suggestedColorCode(selected.textContent.trim())
            : '';
    });
});

document
    .getElementById('variantForm')
    .addEventListener('submit', async event => {
        event.preventDefault();

        const submitButton = document.getElementById('variantSubmitButton');
        const imageInput = document.getElementById('variantImage');
        const image = imageInput.files?.[0];
        const imageUrl = document.getElementById('variantImageUrl').value.trim();
        const colorSelect = document.getElementById('variant_color_id');
        const colorId = colorSelect.value || null;
        const stock = Number(document.getElementById('variant_current_stock').value);
        const maxImageSize = 2 * 1024 * 1024;

        if (!Number.isFinite(stock) || stock < 0) {
            alert('Ingresá un stock válido mayor o igual a cero.');
            return;
        }

        if (image instanceof File && image.size > maxImageSize) {
            alert(
                'La imagen de la variante no puede superar 2 MB. ' +
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

        if (imageUrl && !/^https?:\/\//i.test(imageUrl)) {
            alert('La URL de imagen debe comenzar con http:// o https://.');
            return;
        }

        const sizeSelect = document.getElementById('variant_size_ids');
        const selectedSizes = Array.from(sizeSelect.selectedOptions).map(option => ({
            id: option.value,
            name: option.dataset.rawName || option.textContent.trim()
        }));
        const variantsToSave = selectedSizes.length ? selectedSizes : [{ id: null, name: '' }];

        submitButton.disabled = true;
        submitButton.innerHTML = `
            <span class="spinner-border spinner-border-sm me-1"></span>
            Guardando...
        `;

        try {
            let updated = 0;
            for (const size of variantsToSave) {
                const code = generatedVariantCode(size.name);
                const form = new FormData();
                form.append('product_id', productId);
                if (size.id) form.append('size_id', size.id);
                if (colorId) form.append('color_id', colorId);
                form.append('sku', code);
                form.append('bar_code', code);
                form.append('price_a_with_tax', Number(product?.price_a_with_tax || 0));
                form.append('current_stock', stock);
                if (image?.size) form.append('image', image);
                if (imageUrl) form.append('image_url', imageUrl);

                const res = await fetch(`${window.APP_BASE_URL}/api/product-variants`, {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body: form
                });
                const response = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const errors = response.errors ? Object.values(response.errors).flat().join('\n') : '';
                    throw new Error([response.message || `No se pudo guardar el talle ${size.name || 'sin talle'}.`, errors].filter(Boolean).join('\n'));
                }
                if (response.updated_existing) updated++;
            }

            event.target.reset();
            sizeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            document.getElementById('variantImagePreview').src = '';
            document.getElementById('variantImagePreview').classList.add('d-none');
            document.getElementById('variantImagePlaceholder').classList.remove('d-none');
            await cargarProducto();
            alert(updated ? `${updated} variante(s) existente(s) fueron actualizadas.` : 'Variantes agregadas correctamente.');

        } catch (error) {
            console.error(error);
            alert(error.message || 'No se pudo conectar con el servidor.');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = `
                <i class="ri-add-line me-1"></i>
                Agregar o actualizar variantes
            `;
        }
    });
</script>

@endsection
