{{-- Vista: Listado de Productos. Muestra la consulta principal y las acciones disponibles de Productos. --}}
@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Productos</h4>
        <small class="text-muted">Catálogo, precios, variantes y stock</small>
    </div>

    <a href="{{ url('/demo/products/create') }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>
        Nuevo producto
    </a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Listado de productos</h5>

        <input
            type="text"
            id="search"
            class="form-control form-control-sm"
            style="max-width:260px"
            placeholder="Buscar producto..."
        >
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría / Marca</th>
                    <th class="text-end">Precio</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center">Estado</th>
                    <th>Depósito</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>

            <tbody id="rows">
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        Cargando productos...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @include('components.api-pagination')
</div>

<script>
let products = [];
let searchTimer;

function money(value) {
    return Number(value ?? 0).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS'
    });
}

function stockBadge(stock) {
    stock = Number(stock ?? 0);

    if (stock <= 0) return `<span class="badge bg-label-danger">Sin stock</span>`;
    if (stock <= 3) return `<span class="badge bg-label-warning">${stock}</span>`;

    return `<span class="badge bg-label-success">${stock}</span>`;
}

function renderProducts(items) {
    if (!items.length) {
        rows.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    No hay productos para mostrar
                </td>
            </tr>
        `;
        return;
    }

    rows.innerHTML = items.map(p => {
        const inventory = p.inventory_items?.[0];
        const imageUrl = p.image_full_url ?? (p.image_url ? `${window.APP_BASE_URL}/media/${p.image_url}` : null);

        return `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-md me-3">
                            ${imageUrl
                                ? `<img src="${imageUrl}" alt="${p.name ?? 'Producto'}" class="rounded" style="object-fit:cover;">`
                                : `<span class="avatar-initial rounded bg-label-secondary">
                                    <i class="ri-box-3-line"></i>
                                   </span>`
                            }
                        </div>

                        <div>
                            <h6 class="mb-0">${p.name ?? ''}</h6>
                            <small class="text-muted">
                                Código: ${p.code ?? '-'} · ID ${p.id}
                            </small>
                        </div>
                    </div>
                </td>

                <td>
                    <strong>${p.category?.name ?? p.category ?? '-'}</strong><br>
                    <small class="text-muted">
                        ${p.brand?.name ?? p.brand ?? '-'}
                        ${p.model?.name || p.model ? ' · ' + (p.model?.name ?? p.model) : ''}
                    </small>
                </td>

                <td class="text-end">${money(p.price_a_with_tax)}</td>

                <td class="text-center">${stockBadge(p.current_stock)}</td>

                <td class="text-center">
                    ${p.is_active
                        ? '<span class="badge bg-label-success">Activo</span>'
                        : '<span class="badge bg-label-danger">Inhabilitado</span>'}
                </td>

                <td>${inventory?.warehouse_name ?? '-'}</td>

                <td class="text-end">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-icon btn-outline-secondary" type="button"
                            data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false"
                            aria-label="Acciones del producto ${p.name ?? ''}">
                            <i class="icon-base ri ri-more-2-line"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="${window.APP_BASE_URL}/demo/products/${p.id}"><i class="icon-base ri ri-settings-3-line me-2"></i>Administrar</a>
                            <a class="dropdown-item" href="${window.APP_BASE_URL}/demo/products/${p.id}/edit"><i class="icon-base ri ri-edit-line me-2"></i>Editar</a>
                            <div class="dropdown-divider"></div>
                            <button class="dropdown-item ${p.is_active ? 'text-danger' : 'text-success'}" type="button"
                                data-product-status="${p.id}" data-is-active="${p.is_active ? '1' : '0'}">
                                <i class="icon-base ri ${p.is_active ? 'ri-forbid-line' : 'ri-checkbox-circle-line'} me-2"></i>${p.is_active ? 'Dar de baja' : 'Habilitar'}
                            </button>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

rows.addEventListener('click', async event => {
    const button = event.target.closest('[data-product-status]');
    if (!button) return;

    const productId = Number(button.dataset.productStatus);
    const active = button.dataset.isActive === '1';
    const confirmed = await window.erpConfirm(
        `¿Confirmás que querés ${active ? 'dar de baja' : 'habilitar'} este producto?`,
        {
            title: active ? 'Inhabilitar producto' : 'Habilitar producto',
            icon: active ? 'warning' : 'question',
            confirmButtonText: active ? 'Sí, inhabilitar' : 'Sí, habilitar'
        }
    );
    if (!confirmed) return;

    try {
        const response = await fetch(`${window.APP_BASE_URL}/api/products/${productId}/status`, {
            method: 'PATCH',
            headers: {'Content-Type': 'application/json', Accept: 'application/json'},
            body: JSON.stringify({is_active: !active})
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudo modificar el estado del producto.');

        const product = products.find(item => Number(item.id) === productId);
        if (product) product.is_active = data.product.is_active;
        renderProducts(products);
        await Swal.fire({
            icon: 'success',
            title: active ? 'Producto inhabilitado' : 'Producto habilitado',
            text: data.message,
            confirmButtonText: 'Aceptar'
        });
    } catch (error) {
        await Swal.fire({icon: 'error', title: 'No se pudo actualizar', text: error.message});
    }
});

function loadProducts(page = 1) {
    const params = new URLSearchParams({ page });
    const query = search.value.trim();
    if (query) params.set('search', query);

    fetch(`${window.APP_BASE_URL}/api/products?${params}`)
        .then(r => r.json())
        .then(data => {
            products = data.data ?? data;
            renderProducts(products);
            renderApiPagination(data, loadProducts);
        })
        .catch(error => {
            console.error(error);
            rows.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-danger">
                        Error al cargar productos
                    </td>
                </tr>
            `;
        });
}

search.addEventListener('input', e => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadProducts(1), 300);
});

loadProducts();
</script>

@endsection
