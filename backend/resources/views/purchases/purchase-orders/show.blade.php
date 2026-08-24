{{-- Vista: Detalle de Órdenes de Compra. Muestra la información completa de un registro de Órdenes de Compra. --}}
@extends('layouts.app')

@section('content')

<a href="{{ url('/demo/purchase-orders') }}" class="btn btn-secondary mb-4">
    Volver
</a>

<div id="orderBox">
    <div class="text-center py-5">
        Cargando...
    </div>
</div>

<script>
const orderId = @json($purchaseOrderId);
let loadedOrder = null;

function money(value) {
    return Number(value ?? 0).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS'
    });
}

function statusBadge(status) {
    const value = String(status ?? '').toLowerCase();

    if (
        value.includes('aprobada') ||
        value.includes('recibida') ||
        value.includes('completada')
    ) {
        return 'bg-label-success';
    }

    if (
        value.includes('pendiente') ||
        value.includes('borrador') ||
        value.includes('parcial')
    ) {
        return 'bg-label-warning';
    }

    if (
        value.includes('anulada') ||
        value.includes('cancelada') ||
        value.includes('rechazada')
    ) {
        return 'bg-label-danger';
    }

    return 'bg-label-secondary';
}

fetch(`${window.APP_BASE_URL}/api/purchase-orders/${orderId}`, {
    headers: {
        Accept: 'application/json'
    }
})
    .then(async response => {
        const data = await response.json();

        if (!response.ok) {
            throw new Error(
                data.message ?? 'No se pudo cargar la orden de compra.'
            );
        }

        return data;
    })
    .then(order => {
        loadedOrder = order;
        const items = order.items ?? [];

        const totalQuantity = items.reduce(
            (sum, item) => sum + Number(item.quantity ?? 0),
            0
        );

        const rows = items.length
            ? items.map(item => `
                <tr>
                    <td class="fw-semibold">
                        ${item.product?.code ?? item.product_code ?? '-'}
                    </td>

                    <td>
                        <div class="fw-semibold">
                            ${
                                item.product?.name ??
                                item.product_name ??
                                item.description ??
                                '-'
                            }
                        </div>

                        ${
                            item.description &&
                            item.description !== item.product?.name
                                ? `
                                    <small class="text-muted">
                                        ${item.description}
                                    </small>
                                `
                                : ''
                        }
                    </td>

                    <td class="text-end">
                        ${Number(item.quantity ?? 0).toLocaleString('es-AR')}
                    </td>

                    <td class="text-end">
                        ${money(item.unit_price)}
                    </td>

                    <td class="text-end fw-semibold">
                        ${money(item.total_amount)}
                    </td>
                </tr>
            `).join('')
            : `
                <tr>
                    <td
                        colspan="5"
                        class="text-center text-muted py-5"
                    >
                        La orden no tiene productos registrados.
                    </td>
                </tr>
            `;

        orderBox.innerHTML = `
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h3 class="mb-0">
                            Orden de compra
                        </h3>

                        <span class="badge bg-label-primary">
                            ${order.order_number ?? `Nº ${order.id}`}
                        </span>
                    </div>

                    <small class="text-muted">
                        Detalle de la solicitud de compra
                    </small>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge ${statusBadge(order.status_name)} fs-6">
                        ${order.status_name ?? 'Sin estado'}
                    </span>

                    <button
                        type="button"
                        class="btn btn-outline-primary btn-sm"
                        onclick="sendOrderByEmail()"
                    >
                        Enviar por email
                    </button>

                    <button
                        type="button"
                        class="btn btn-outline-success btn-sm"
                        onclick="window.print()"
                    >
                        Imprimir
                    </button>

                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm"
                        onclick="duplicateOrder()"
                    >
                        Duplicar
                    </button>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <small class="text-muted d-block mb-1">
                                Proveedor
                            </small>

                            <span class="fw-semibold">
                                ${order.provider_name ?? '-'}
                            </span>
                        </div>

                        <div class="col-md-2">
                            <small class="text-muted d-block mb-1">
                                Número
                            </small>

                            <span>
                                ${order.order_number ?? '-'}
                            </span>
                        </div>

                        <div class="col-md-2">
                            <small class="text-muted d-block mb-1">
                                Fecha
                            </small>

                            <span>
                                ${formatDateTime(order.issue_date)}
                            </span>
                        </div>

                        <div class="col-md-2">
                            <small class="text-muted d-block mb-1">
                                Moneda
                            </small>

                            <span>
                                ${order.currency_name ?? 'Pesos'}
                            </span>
                        </div>

                        <div class="col-md-2">
                            <small class="text-muted d-block mb-1">
                                Estado
                            </small>

                            <span>
                                ${order.status_name ?? '-'}
                            </span>
                        </div>

                        ${
                            order.expected_delivery_date
                                ? `
                                    <div class="col-md-3">
                                        <small class="text-muted d-block mb-1">
                                            Entrega estimada
                                        </small>

                                        <span>
                                            ${formatDateTime(order.expected_delivery_date)}
                                        </span>
                                    </div>
                                `
                                : ''
                        }

                        ${
                            order.branch_name
                                ? `
                                    <div class="col-md-3">
                                        <small class="text-muted d-block mb-1">
                                            Sucursal
                                        </small>

                                        <span>
                                            ${order.branch_name}
                                        </span>
                                    </div>
                                `
                                : ''
                        }

                        ${
                            order.warehouse_name
                                ? `
                                    <div class="col-md-3">
                                        <small class="text-muted d-block mb-1">
                                            Depósito
                                        </small>

                                        <span>
                                            ${order.warehouse_name}
                                        </span>
                                    </div>
                                `
                                : ''
                        }

                        ${
                            order.payment_condition_name
                                ? `
                                    <div class="col-md-3">
                                        <small class="text-muted d-block mb-1">
                                            Condición de pago
                                        </small>

                                        <span>
                                            ${order.payment_condition_name}
                                        </span>
                                    </div>
                                `
                                : ''
                        }
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h3 class="mb-1">
                                ${items.length}
                            </h3>

                            <small class="text-muted">
                                Productos
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h3 class="mb-1">
                                ${totalQuantity.toLocaleString('es-AR')}
                            </h3>

                            <small class="text-muted">
                                Unidades solicitadas
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h3 class="mb-1">
                                ${money(order.total_amount)}
                            </h3>

                            <small class="text-muted">
                                Total de la orden
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        Productos solicitados
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th class="text-end">Cantidad</th>
                                <th class="text-end">Precio unitario</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>

                        <tbody>
                            ${rows}
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row justify-content-end">
                <div class="col-md-5 col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            ${
                                Number(order.subtotal ?? 0) !== 0
                                    ? `
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="text-muted">
                                                Subtotal
                                            </span>

                                            <span class="fw-semibold">
                                                ${money(order.subtotal)}
                                            </span>
                                        </div>
                                    `
                                    : ''
                            }

                            ${
                                Number(order.discount_amount ?? 0) !== 0
                                    ? `
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="text-muted">
                                                Descuento
                                            </span>

                                            <span class="fw-semibold">
                                                -${money(order.discount_amount)}
                                            </span>
                                        </div>
                                    `
                                    : ''
                            }

                            ${
                                Number(order.tax_amount ?? 0) !== 0
                                    ? `
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="text-muted">
                                                Impuestos
                                            </span>

                                            <span class="fw-semibold">
                                                ${money(order.tax_amount)}
                                            </span>
                                        </div>
                                    `
                                    : ''
                            }

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold fs-5">
                                    Total
                                </span>

                                <span class="fw-bold fs-3">
                                    ${money(order.total_amount)}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            ${
                order.notes
                    ? `
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    Observaciones
                                </h5>
                            </div>

                            <div class="card-body">
                                ${order.notes}
                            </div>
                        </div>
                    `
                    : ''
            }
        `;
    })
    .catch(error => {
        orderBox.innerHTML = `
            <div class="alert alert-danger">
                ${error.message}
            </div>
        `;
    });

function sendOrderByEmail() {
    const email = loadedOrder?.provider?.email;

    if (!email) {
        alert('El proveedor no tiene un correo electrónico registrado.');
        return;
    }

    const items = (loadedOrder.items ?? []).map(item => {
        const description = item.product?.name ?? item.description ?? 'Producto';
        return `- ${description}: ${Number(item.quantity ?? 0).toLocaleString('es-AR')} x ${money(item.unit_price)} = ${money(item.total_amount)}`;
    });
    const number = loadedOrder.order_number ?? `#${loadedOrder.id}`;
    const subject = `Orden de compra ${number}`;
    const body = [
        `Estimados ${loadedOrder.provider?.name ?? loadedOrder.provider_name ?? 'proveedor'},`,
        '',
        `Adjuntamos el detalle de la orden de compra ${number}.`,
        `Fecha: ${formatDateTime(loadedOrder.issue_date)}`,
        '',
        'Productos:',
        ...items,
        '',
        `Total: ${money(loadedOrder.total_amount)}`,
        '',
        'Saludos.'
    ].join('\n');

    window.location.href = `mailto:${encodeURIComponent(email)}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
}

function duplicateOrder() {
    alert('La función para duplicar la orden todavía no está conectada.');
}
</script>

@endsection
