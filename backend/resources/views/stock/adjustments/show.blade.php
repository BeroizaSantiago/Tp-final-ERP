{{-- Vista de solo lectura del detalle de un ajuste de stock. --}}
@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h3 class="mb-1">Ajuste de Stock</h3>
        <div class="text-muted" id="adjustmentSubtitle">Cargando detalle...</div>
    </div>
    <a href="{{ url('/demo/stock/adjustments') }}" class="btn btn-outline-secondary">
        Volver al listado
    </a>
</div>

<div id="loadingCard" class="card">
    <div class="card-body text-center py-5 text-muted">Cargando ajuste...</div>
</div>

<div id="errorCard" class="alert alert-danger d-none"></div>

<div id="detailContent" class="d-none">
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Datos del ajuste</h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-sm-6 col-lg-3">
                    <small class="text-muted d-block">Número</small>
                    <strong id="adjustmentNumber">-</strong>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <small class="text-muted d-block">Fecha</small>
                    <span id="adjustmentDate">-</span>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <small class="text-muted d-block">Sucursal</small>
                    <span id="adjustmentBranch">-</span>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <small class="text-muted d-block">Depósito</small>
                    <span id="adjustmentWarehouse">-</span>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <small class="text-muted d-block">Motivo</small>
                    <span id="adjustmentReason">-</span>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <small class="text-muted d-block">Usuario</small>
                    <span id="adjustmentUser">-</span>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <small class="text-muted d-block">Estado</small>
                    <span id="adjustmentStatus"></span>
                </div>
                <div class="col-12">
                    <small class="text-muted d-block">Observaciones</small>
                    <span id="adjustmentNotes">-</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Productos afectados</h5>
            <span class="badge bg-label-secondary" id="itemsCount">0 ítems</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Código de barras</th>
                        <th>Talle</th>
                        <th>Color</th>
                        <th class="text-end">Stock anterior</th>
                        <th class="text-end">Cantidad ajustada</th>
                        <th class="text-end">Stock resultante</th>
                    </tr>
                </thead>
                <tbody id="detailRows"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
const stockAdjustmentId = @json($stockAdjustmentId);

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function display(value) {
    return value === null || value === undefined || value === '' ? '-' : escapeHtml(value);
}

function quantity(value) {
    const number = Number(value ?? 0);
    return number.toLocaleString('es-AR', { maximumFractionDigits: 4 });
}

function signedQuantity(value) {
    const number = Number(value ?? 0);
    const sign = number > 0 ? '+' : '';
    const cssClass = number > 0 ? 'text-success' : (number < 0 ? 'text-danger' : '');
    return `<strong class="${cssClass}">${sign}${quantity(number)}</strong>`;
}

function formatDate(value) {
    return window.formatDateTime(value);
}

async function loadAdjustment() {
    try {
        const response = await fetch(`${window.APP_BASE_URL}/api/stock-adjustments/${stockAdjustmentId}`, {
            headers: { Accept: 'application/json' }
        });

        if (!response.ok) {
            throw new Error(response.status === 404 ? 'El ajuste solicitado no existe.' : 'No se pudo cargar el ajuste.');
        }

        const adjustment = await response.json();
        const movements = adjustment.movements ?? [];

        adjustmentSubtitle.textContent = adjustment.number ? `Detalle del ajuste ${adjustment.number}` : `Detalle del ajuste #${adjustment.id}`;
        adjustmentNumber.textContent = adjustment.number ?? `#${adjustment.id}`;
        adjustmentDate.innerHTML = formatDate(adjustment.date ?? adjustment.created_at);
        adjustmentBranch.innerHTML = display(adjustment.branch_name);
        adjustmentWarehouse.innerHTML = display(adjustment.warehouse_name);
        adjustmentReason.innerHTML = display(adjustment.reason?.name ?? adjustment.adjustment_stock_reason);
        adjustmentUser.innerHTML = display(adjustment.created_by);
        adjustmentNotes.innerHTML = display(adjustment.notes);
        adjustmentStatus.innerHTML = adjustment.is_active
            ? '<span class="badge bg-label-success">Aplicado</span>'
            : '<span class="badge bg-label-danger">Anulado</span>';
        itemsCount.textContent = `${movements.length} ${movements.length === 1 ? 'ítem' : 'ítems'}`;

        detailRows.innerHTML = movements.length
            ? movements.map(movement => {
                const item = movement.inventory_item ?? {};
                const product = item.product ?? {};
                const variant = item.variant ?? {};

                return `
                    <tr>
                        <td>${display(product.code ?? item.code)}</td>
                        <td><strong>${display(product.name ?? item.product_name)}</strong></td>
                        <td>${display(variant.bar_code ?? item.bar_code)}</td>
                        <td>${display(variant.size?.name ?? item.size_name)}</td>
                        <td>${display(variant.color?.name ?? item.color_name)}</td>
                        <td class="text-end">${quantity(movement.stock_before)}</td>
                        <td class="text-end">${signedQuantity(movement.quantity)}</td>
                        <td class="text-end">${quantity(movement.stock_after)}</td>
                    </tr>
                `;
            }).join('')
            : '<tr><td colspan="8" class="text-center py-4 text-muted">Este ajuste no tiene movimientos vinculados.</td></tr>';

        loadingCard.classList.add('d-none');
        detailContent.classList.remove('d-none');
    } catch (error) {
        loadingCard.classList.add('d-none');
        errorCard.textContent = error.message;
        errorCard.classList.remove('d-none');
    }
}

loadAdjustment();
</script>
@endsection
