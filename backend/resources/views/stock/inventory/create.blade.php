{{-- Vista: Nuevo registro de Inventario. Muestra el formulario para crear un registro de Inventario. --}}
@extends('layouts.app')

@section('content')

<h3 class="mb-4">Cargar Stock Inicial</h3>

<div class="card">
    <div class="card-body">
        <form id="form">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>Producto</label>
                    <select class="form-select" id="product_id" data-remote-url="{{ url('/api/products') }}" data-remote-placeholder="Buscar por nombre, código o código de barras..." required><option value=""></option></select>
                </div>

                <div class="col-md-3 mb-3">
                    <label>Variante</label>
                    <select class="form-select" id="product_variant_id" required></select>
                </div>

                <div class="col-md-3 mb-3">
                    <label>Depósito</label>
                    <select class="form-select" id="warehouse_name" required></select>
                </div>

                <div class="col-md-2 mb-3">
                    <label>Sucursal</label>
                    <select class="form-select" id="branch_name" data-stock-branch data-warehouse-target="warehouse_name" required></select>
                </div>

                <div class="col-md-3 mb-3">
                    <label>Stock inicial</label>
                    <input class="form-control" id="current_stock" type="number" step="0.01" value="0" required>
                </div>

                <div class="col-md-3 mb-3">
                    <label>Costo unitario</label>
                    <input class="form-control" id="valued_item" type="number" min="0" step="0.01" value="0" required>
                    <small class="text-muted">Costo de una unidad de esta variante.</small>
                </div>

                <div class="col-md-3 mb-3">
                    <label>Stock mínimo</label>
                    <input class="form-control" id="min_stock" type="number" step="0.01" value="0">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Stock reposición</label>
                    <input class="form-control" id="reposition_stock" type="number" step="0.01" value="0">
                </div>
            </div>

            <button class="btn btn-success">Guardar stock</button>
            <a href="{{ url('/demo/stock/inventory') }}" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

<script>
let products = [];

async function cargarProductos() {
    erpEnhanceRemoteSelect(product_id);
}

async function cargarVariantes() {
    let product = null;
    if (product_id.value) {
        const response = await fetch(`${window.APP_BASE_URL}/api/products/${product_id.value}`, {headers: {Accept: 'application/json'}});
        const text = await response.text();
        if (!response.ok || !text.trim()) {
            erpAlert('El producto seleccionado no existe en la base de datos.');
            product_id._remoteSelect?.clear();
            product_variant_id.innerHTML = '<option value="">Seleccione un producto válido...</option>';
            return;
        }
        try {
            product = JSON.parse(text);
        } catch (error) {
            erpAlert('No se pudo interpretar la información del producto seleccionado.');
            return;
        }
    }

    product_variant_id.innerHTML = '<option value="">Seleccione...</option>';

    (product?.variants ?? []).forEach(v => {
        product_variant_id.innerHTML += `
            <option value="${v.id}">
                ${v.size?.name ?? '-'} / ${v.color?.name ?? '-'}
            </option>
        `;
    });
}

product_id.addEventListener('change', cargarVariantes);

form.addEventListener('submit', async e => {
    e.preventDefault();

    const payload = {
        product_id: Number(product_id.value),
        product_variant_id: Number(product_variant_id.value),
        branch_name: branch_name.value,
        warehouse_name: warehouse_name.value,
        current_stock: Number(current_stock.value),
        valued_item: Number(valued_item.value),
        min_stock: Number(min_stock.value),
        reposition_stock: Number(reposition_stock.value),
        currency_symbol: '$'
    };

    const res = await fetch(`${window.APP_BASE_URL}/api/inventory-items`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json'
        },
        body: JSON.stringify(payload)
    });

    if (res.ok) {
        location.href = `${window.APP_BASE_URL}/demo/stock/inventory`;
    } else {
        const text = await res.text();
        let error = {};
        try { error = text ? JSON.parse(text) : {}; } catch (parseError) {}
        const validation = error.errors ? Object.values(error.errors).flat()[0] : null;
        erpAlert(validation ?? error.message ?? 'No se pudo guardar el stock. Verificá que el producto y la variante existan en la base de datos.');
    }
});

cargarProductos();
</script>

@endsection
