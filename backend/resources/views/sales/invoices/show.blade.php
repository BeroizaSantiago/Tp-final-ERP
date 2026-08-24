{{-- Vista: Detalle de Facturas de Venta. Muestra la información completa de un registro de Facturas de Venta. --}}
@extends('layouts.app')

@section('content')

<a href="{{ url('/demo/sales') }}" class="btn btn-secondary mb-3">Volver</a>

<div id="invoiceBox">Cargando...</div>

<script>
const invoiceId = "{{ $invoiceId }}";

function money(value) {
    return Number(value ?? 0).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS'
    });
}

function statusBadge(status) {
    const text = String(status ?? '').toLowerCase();

    if (text.includes('cob') || text.includes('pag')) {
        return `<span class="badge bg-label-success">${status}</span>`;
    }

    if (text.includes('pend')) {
        return `<span class="badge bg-label-warning">${status}</span>`;
    }

    if (text.includes('anu')) {
        return `<span class="badge bg-label-danger">${status}</span>`;
    }

    return `<span class="badge bg-label-secondary">${status ?? '-'}</span>`;
}

fetch(`${window.APP_BASE_URL}/api/invoices/${invoiceId}`)
    .then(r => r.json())
    .then(invoice => {
        const items = invoice.items ?? [];
        const isInternal = Boolean(invoice.is_internal_receipt);

        const rows = items.map(item => `
            <tr>
                <td>${item.product_code ?? ''}</td>
                <td>
                    <strong>${item.product_name ?? item.description ?? ''}</strong><br>
                    <small class="text-muted">
                        Talle: ${item.variant?.size?.name ?? item.size_name ?? '-'}
                        · Color: ${item.variant?.color?.name ?? item.color_name ?? '-'}
                    </small>
                </td>
                <td class="text-end">${item.quantity ?? ''}</td>
                <td class="text-end">${money(item.unit_price_with_taxes)}</td>
                <td class="text-end">${item.discount_percentage ?? 0}%</td>
                <td class="text-end">${item.tax_aliquot_percentage ?? 0}%</td>
                <td class="text-end"><strong>${money(item.total_amount)}</strong></td>
            </tr>
        `).join('');

        invoiceBox.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">
                        ${isInternal ? 'Comprobante interno de venta' : 'Factura de Venta'}
                        ${statusBadge(invoice.status_name)}
                    </h4>
                    <small class="text-muted">
                        ${invoice.display_number ?? invoice.full_number ?? `${invoice.letter ?? ''}-${invoice.first_number ?? ''}`}
                    </small>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="${window.APP_BASE_URL}/demo/sales/${invoice.id}/payment-method" class="btn btn-dark btn-sm">
                        Formas de pago
                    </a>

                    ${!isInternal ? `
                        <a href="${window.APP_BASE_URL}/demo/sales/${invoice.id}/pdf" target="_blank" class="btn btn-secondary btn-sm">
                            Ver PDF
                        </a>
                    ` : ''}

                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="mb-3">Datos del comprobante</h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted">Cliente</small><br>
                                    <strong>${invoice.customer_name ?? '-'}</strong>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <small class="text-muted">Tipo</small><br>
                                    <strong>${isInternal ? 'Interno' : `Factura ${invoice.letter ?? '-'}`}</strong>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <small class="text-muted">Pto. Venta</small><br>
                                    <strong>${invoice.first_number ?? '-'}</strong>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <small class="text-muted">Fecha emisión</small><br>
                                    <strong>${formatDateTime(invoice.issue_date)}</strong>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <small class="text-muted">Vencimiento</small><br>
                                    <strong>${formatDateTime(invoice.payment_due_date)}</strong>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <small class="text-muted">Moneda</small><br>
                                    <strong>${invoice.currency_name ?? 'Pesos'}</strong>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <small class="text-muted">Condición</small><br>
                                    <strong>${invoice.payment_condition_name ?? 'CONTADO'}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="mb-3">${isInternal ? 'Tipo de comprobante' : 'Estado ARCA'}</h5>

                            ${isInternal ? `
                                <span class="badge bg-label-info mb-3">Comprobante interno</span>
                                <p class="text-muted mb-0">
                                    Registro interno para control comercial, de stock y caja.
                                </p>
                            ` : invoice.arca_cae ? `
                                <span class="badge bg-label-success mb-3">Autorizada</span>
                                <p class="mb-1"><strong>CAE:</strong> ${invoice.arca_cae}</p>
                                <p class="mb-0"><strong>Vencimiento:</strong> ${formatDateTime(invoice.arca_cae_expiration)}</p>
                            ` : `
                                <span class="badge bg-label-secondary mb-3">Sin autorizar</span>
                                <p class="text-muted mb-0">
                                    Este comprobante puede verse como PDF interno hasta ser autorizado.
                                </p>
                            `}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Productos</h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th class="text-end">Cant.</th>
                                <th class="text-end">P. Unit.</th>
                                <th class="text-end">Desc.</th>
                                <th class="text-end">IVA</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>

                        <tbody>
                            ${rows || `<tr><td colspan="7" class="text-center text-muted">Sin productos</td></tr>`}
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row justify-content-end">
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Gravado</span>
                                <strong>${money(invoice.taxed_amount)}</strong>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span>No gravado</span>
                                <strong>${money(invoice.non_taxed_amount)}</strong>
                            </div>

                            <div class="d-flex justify-content-between mb-3">
                                <span>IVA</span>
                                <strong>${money(invoice.tax_amount)}</strong>
                            </div>

                            ${Number(invoice.promotion_discount_amount ?? 0) > 0 ? `
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal original</span>
                                    <strong>${money(Number(invoice.total_amount) + Number(invoice.promotion_discount_amount))}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3 text-success">
                                    <span>Promoción ${invoice.sales_promotion?.name ? '· ' + invoice.sales_promotion.name : ''}</span>
                                    <strong>-${money(invoice.promotion_discount_amount)}</strong>
                                </div>
                            ` : ''}

                            <hr>

                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Total final</h5>
                                <h3 class="mb-0">${money(invoice.total_amount)}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
</script>

@endsection
