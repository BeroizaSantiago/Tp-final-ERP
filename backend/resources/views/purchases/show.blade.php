{{-- Vista: Detalle de Compras. Muestra la información completa de un registro de Compras. --}}
@extends('layouts.app')

@section('content')

<a href="{{ url('/demo/purchases') }}" class="btn btn-secondary mb-4">
    Volver
</a>

<div id="box">
    <div class="text-center py-5">
        Cargando...
    </div>
</div>

<script>
const purchaseId = @json($purchaseId);

function money(value) {
    return Number(value ?? 0).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS'
    });
}

function statusBadge(status) {
    const statusName = String(status ?? '').toLowerCase();

    if (
        statusName.includes('pagada') ||
        statusName.includes('recibida') ||
        statusName.includes('completada')
    ) {
        return 'bg-label-success';
    }

    if (
        statusName.includes('pendiente') ||
        statusName.includes('parcial')
    ) {
        return 'bg-label-warning';
    }

    if (
        statusName.includes('anulada') ||
        statusName.includes('cancelada')
    ) {
        return 'bg-label-danger';
    }

    return 'bg-label-secondary';
}

fetch(`${window.APP_BASE_URL}/api/purchases/${purchaseId}`, {
    headers: {
        Accept: 'application/json'
    }
})
    .then(async response => {
        const data = await response.json();

        if (!response.ok) {
            throw new Error(
                data.message ?? 'No se pudo cargar la compra.'
            );
        }

        return data;
    })
    .then(purchase => {
        const items = purchase.items ?? [];

        const totalQuantity = items.reduce(
            (sum, item) => sum + Number(item.quantity ?? 0),
            0
        );

        const subtotal = Number(
            purchase.taxed_amount ??
            purchase.subtotal ??
            items.reduce((sum, item) => {
                return sum +
                    (
                        Number(item.quantity ?? 0) *
                        Number(item.unit_price ?? 0)
                    );
            }, 0)
        );

        const taxAmount = Number(
            purchase.tax_amount ??
            items.reduce((sum, item) => {
                const quantity = Number(item.quantity ?? 0);
                const unitPrice = Number(item.unit_price ?? 0);
                const taxPercentage = Number(
                    item.tax_percentage ?? 0
                );

                return sum +
                    (
                        quantity *
                        unitPrice *
                        taxPercentage /
                        100
                    );
            }, 0)
        );

        const rows = items.length
            ? items.map(item => `
                <tr>
                    <td class="fw-semibold">
                        ${item.product_code ?? '-'}
                    </td>

                    <td>
                        <div class="fw-semibold">
                            ${item.product_name ?? '-'}
                        </div>

                        ${
                            item.description
                                ? `
                                    <small class="text-muted">
                                        ${item.description}
                                    </small>
                                `
                                : ''
                        }
                    </td>

                    <td>
                        ${item.size_name ?? '-'}
                    </td>

                    <td>
                        ${item.color_name ?? '-'}
                    </td>

                    <td class="text-end">
                        ${Number(item.quantity ?? 0).toLocaleString('es-AR')}
                    </td>

                    <td class="text-end">
                        ${money(item.unit_price)}
                    </td>

                    <td class="text-end">
                        ${Number(item.tax_percentage ?? 0).toLocaleString('es-AR')}%
                    </td>

                    <td class="text-end fw-semibold">
                        ${money(item.total_amount)}
                    </td>
                </tr>
            `).join('')
            : `
                <tr>
                    <td
                        colspan="8"
                        class="text-center text-muted py-5"
                    >
                        La compra no tiene productos registrados.
                    </td>
                </tr>
            `;

        box.innerHTML = `
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h3 class="mb-0">
                            Compra
                        </h3>

                        <span class="badge bg-label-primary">
                            ${purchase.full_number ?? `Nº ${purchase.id}`}
                        </span>
                    </div>

                    <small class="text-muted">
                        Detalle del comprobante de compra
                    </small>
                </div>

                <span class="badge ${statusBadge(purchase.status_name)} fs-6">
                    ${purchase.status_name ?? 'Sin estado'}
                </span>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <small class="text-muted d-block mb-1">
                                Proveedor
                            </small>

                            <span class="fw-semibold">
                                ${purchase.provider_name ?? '-'}
                            </span>
                        </div>

                        <div class="col-md-2">
                            <small class="text-muted d-block mb-1">
                                Tipo de comprobante
                            </small>

                            <span>
                                ${purchase.receipt_type_name ?? '-'}
                            </span>
                        </div>

                        <div class="col-md-2">
                            <small class="text-muted d-block mb-1">
                                Número
                            </small>

                            <span>
                                ${purchase.full_number ?? '-'}
                            </span>
                        </div>

                        <div class="col-md-2">
                            <small class="text-muted d-block mb-1">
                                Fecha
                            </small>

                            <span>
                                ${formatDateTime(purchase.issue_date)}
                            </span>
                        </div>

                        <div class="col-md-2">
                            <small class="text-muted d-block mb-1">
                                Moneda
                            </small>

                            <span>
                                ${purchase.currency_name ?? 'Pesos'}
                            </span>
                        </div>

                        ${
                            purchase.payment_due_date
                                ? `
                                    <div class="col-md-3">
                                        <small class="text-muted d-block mb-1">
                                            Vencimiento
                                        </small>

                                        <span>
                                            ${formatDateTime(purchase.payment_due_date)}
                                        </span>
                                    </div>
                                `
                                : ''
                        }

                        ${
                            purchase.warehouse_name
                                ? `
                                    <div class="col-md-3">
                                        <small class="text-muted d-block mb-1">
                                            Depósito
                                        </small>

                                        <span>
                                            ${purchase.warehouse_name}
                                        </span>
                                    </div>
                                `
                                : ''
                        }

                        ${
                            purchase.payment_condition_name
                                ? `
                                    <div class="col-md-3">
                                        <small class="text-muted d-block mb-1">
                                            Condición de pago
                                        </small>

                                        <span>
                                            ${purchase.payment_condition_name}
                                        </span>
                                    </div>
                                `
                                : ''
                        }

                        ${
                            purchase.exchange_rate
                                ? `
                                    <div class="col-md-3">
                                        <small class="text-muted d-block mb-1">
                                            Cotización
                                        </small>

                                        <span>
                                            ${Number(purchase.exchange_rate).toLocaleString('es-AR')}
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
                                Unidades
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h3 class="mb-1">
                                ${money(purchase.total_amount)}
                            </h3>

                            <small class="text-muted">
                                Total de compra
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        Productos de la compra
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Talle</th>
                                <th>Color</th>
                                <th class="text-end">Cantidad</th>
                                <th class="text-end">Precio unitario</th>
                                <th class="text-end">IVA</th>
                                <th class="text-end">Total</th>
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
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">
                                    Subtotal
                                </span>

                                <span class="fw-semibold">
                                    ${money(subtotal)}
                                </span>
                            </div>

                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">
                                    IVA
                                </span>

                                <span class="fw-semibold">
                                    ${money(taxAmount)}
                                </span>
                            </div>

                            ${
                                Number(purchase.non_taxed_amount ?? 0) !== 0
                                    ? `
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="text-muted">
                                                No gravado
                                            </span>

                                            <span class="fw-semibold">
                                                ${money(purchase.non_taxed_amount)}
                                            </span>
                                        </div>
                                    `
                                    : ''
                            }

                            ${
                                Number(purchase.exempt_amount ?? 0) !== 0
                                    ? `
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="text-muted">
                                                Exento
                                            </span>

                                            <span class="fw-semibold">
                                                ${money(purchase.exempt_amount)}
                                            </span>
                                        </div>
                                    `
                                    : ''
                            }

                            <hr>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold fs-5">
                                    Total
                                </span>

                                <span class="fw-bold fs-3">
                                    ${money(purchase.total_amount)}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            ${
                purchase.notes
                    ? `
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    Observaciones
                                </h5>
                            </div>

                            <div class="card-body">
                                ${purchase.notes}
                            </div>
                        </div>
                    `
                    : ''
            }
        `;
    })
    .catch(error => {
        box.innerHTML = `
            <div class="alert alert-danger">
                ${error.message}
            </div>
        `;
    });
</script>

@endsection
