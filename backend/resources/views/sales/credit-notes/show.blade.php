{{-- Vista: Detalle de Notas de Crédito. Muestra la información completa de un registro de Notas de Crédito. --}}
@extends('layouts.app')

@section('content')

<div class="mb-3"><x-action-button action="back" :href="url('/demo/sales/credit-notes')" /></div>

<div id="invoiceBox">Cargando...</div>

<script>
const invoiceId = "{{ $invoiceId }}";

function money(value) {
    return Number(value ?? 0).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS'
    });
}

fetch(`${window.APP_BASE_URL}/api/credit-notes/${invoiceId}`)
    .then(r => r.json())
    .then(invoice => {
        const rows = (invoice.items ?? []).map(item => `
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
                        Nota de Crédito
                        <span class="badge bg-label-warning">${invoice.status_name ?? ''}</span>
                    </h4>
                    <small class="text-muted">
                        ${invoice.full_number ?? `${invoice.letter ?? ''}-${invoice.first_number ?? ''}`}
                    </small>
                </div>

                <div class="d-flex gap-2">
                    ${!invoice.is_internal_receipt && !invoice.arca_cae ? `
                        <button type="button" class="btn btn-warning btn-sm" id="authorizeArcaButton">
                            Autorizar ARCA
                        </button>
                    ` : ''}
                    <button class="btn btn-success btn-sm">Descargar</button>
                    <button class="btn btn-dark btn-sm">Reimprimir</button>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Datos del comprobante</h5>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <small class="text-muted">Cliente</small><br>
                            <strong>${invoice.customer_name ?? '-'}</strong>
                        </div>

                        <div class="col-md-2 mb-3">
                            <small class="text-muted">Comprobante</small><br>
                            <strong>${invoice.receipt_type_name ?? 'Nota de Crédito'}</strong>
                        </div>

                        <div class="col-md-2 mb-3">
                            <small class="text-muted">Pto. Venta</small><br>
                            <strong>${invoice.first_number ?? '-'}</strong>
                        </div>

                        <div class="col-md-2 mb-3">
                            <small class="text-muted">Fecha emisión</small><br>
                            <strong>${formatDateTime(invoice.issue_date)}</strong>
                        </div>

                        <div class="col-md-2 mb-3">
                            <small class="text-muted">Relacionado</small><br>
                            <strong>${invoice.related_document_full_name ?? '-'}</strong>
                        </div>

                        <div class="col-md-3 mb-3">
                            <small class="text-muted">Motivo</small><br>
                            <strong>${invoice.credit_note_reason?.name ?? invoice.credit_note_reason_name ?? '-'}</strong>
                        </div>

                        <div class="col-md-3 mb-3">
                            <small class="text-muted">CAE</small><br>
                            <strong>${invoice.arca_cae ?? (invoice.is_internal_receipt ? 'No aplica' : 'Pendiente')}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Productos ajustados</h5>
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

                            <hr>

                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Total</h5>
                                <h3 class="mb-0">${money(invoice.total_amount)}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('authorizeArcaButton')?.addEventListener('click', authorizeArca);
    });

async function authorizeArca(event) {
    const button = event.currentTarget;
    button.disabled = true;
    button.textContent = 'Autorizando...';

    try {
        const response = await fetch(`${window.APP_BASE_URL}/api/credit-notes/${invoiceId}/authorize`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'ARCA rechazó la nota de crédito.');

        await Swal.fire({ icon: 'success', title: 'Nota autorizada', text: data.message });
        window.location.reload();
    } catch (error) {
        await Swal.fire({ icon: 'error', title: 'No se pudo autorizar', text: error.message });
        button.disabled = false;
        button.textContent = 'Autorizar ARCA';
    }
}
</script>

@endsection
