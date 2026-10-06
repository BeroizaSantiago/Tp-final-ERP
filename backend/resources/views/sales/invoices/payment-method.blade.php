{{-- Vista: Formas de pago de Facturas de Venta. Permite registrar y consultar los medios de pago de Facturas de Venta. --}}
@extends('layouts.app')

@section('content')

<button type="button" class="btn btn-secondary mb-3" onclick="backToSaleDraft(this)">
    <i class="ri-arrow-left-line me-1"></i> Volver atras
</button>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Formas de Pago</h4>
        <small class="text-muted">Registrar cobros de la factura</small>
    </div>
</div>

<div id="box">Cargando...</div>

<div class="modal fade" id="mercadoPagoQrModal" tabindex="-1" aria-labelledby="mercadoPagoQrTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mercadoPagoQrTitle">Pagar con Mercado Pago QR</h5>
            </div>
            <div class="modal-body text-center">
                <div id="mercadoPagoQrStatus" class="alert alert-info">Escaneá el código con la aplicación de Mercado Pago.</div>
                <img id="mercadoPagoQrImage" alt="Código QR de Mercado Pago" class="img-fluid mx-auto d-block mb-3" style="max-width: 320px;">
                <div class="fs-4 fw-bold" id="mercadoPagoQrAmount"></div>
                <small class="text-muted d-block mt-2">La pantalla se actualizará automáticamente cuando se acredite el pago.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" id="mercadoPagoQrCancel" onclick="cancelMercadoPagoQr()">Cancelar orden</button>
            </div>
        </div>
    </div>
</div>

<script>
const invoiceId = "{{ $invoiceId }}";
let invoice = null;
let selectedMethod = 'cash';
let compatiblePromotions = [];
let activeQrPaymentId = null;
let qrPollTimer = null;

const cardPlans = {
    'VISA CREDITO': [
        { name: '1', text: '1 (0%)', recharge: 0 },
        { name: '3', text: '3 (0%)', recharge: 0 },
        { name: '6', text: '6 (23%)', recharge: 0.23 }
    ],
    'MASTERCARD': [
        { name: '1', text: '1 (0%)', recharge: 0 },
        { name: '3', text: '3 (10%)', recharge: 0.10 },
        { name: '6', text: '6 (25%)', recharge: 0.25 }
    ],
    'CABAL': [
        { name: '1', text: '1 (0%)', recharge: 0 }
    ]
};

function money(value) {
    return Number(value ?? 0).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS'
    });
}

function labelMethod(method) {
    return {
        card: 'Tarjeta',
        cash: 'Efectivo',
        transfer: 'Transferencia',
        check: 'Cheque',
    }[method] ?? method;
}

function isAppliedPayment(payment) {
    return payment.status === 'approved' || (!payment.provider && !payment.status);
}

function appliedPaymentAmount(payment) {
    if (!isAppliedPayment(payment)) return 0;

    return payment.payment_method === 'current_account'
        ? Number(payment.amount ?? 0)
        : Number(payment.total_paid ?? 0);
}

async function backToSaleDraft(button) {
    if ((invoice?.payments ?? []).length) {
        alert('La venta ya tiene pagos registrados. Cancelá el pago pendiente o finalizá la venta antes de volver.');
        return;
    }

    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Volviendo...';
    try {
        const response = await fetch(`${window.APP_BASE_URL}/api/invoices/${invoiceId}/draft`, {
            method: 'DELETE',
            headers: { Accept: 'application/json' }
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'No se pudo recuperar el borrador.');
        location.href = `${window.APP_BASE_URL}/demo/sales/create?restore_draft=1`;
    } catch (error) {
        button.disabled = false;
        button.innerHTML = '<i class="ri-arrow-left-line me-1"></i> Volver y agregar productos';
        alert(error.message);
    }
}

function methodButton(method, label, disabled = false) {
    return `
        <button
            type="button"
            class="btn ${selectedMethod === method ? 'btn-primary' : 'btn-outline-primary'}"
            ${disabled ? 'disabled aria-disabled="true"' : ''}
            onclick="selectMethod('${method}')">
            ${label}
        </button>
    `;
}

function render() {
    const payments = invoice.payments ?? [];
    const paid = payments.reduce((total, payment) => total + appliedPaymentAmount(payment), 0);
    const balance = Math.max(0, Number(invoice.total_amount) - paid);
    const isPaid = balance <= 0.005;
    const promotionDiscount = Number(invoice.promotion_discount_amount ?? 0);
    const originalTotal = Number(invoice.total_amount) + promotionDiscount;

    box.innerHTML = `
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                            <div>
                                <small class="text-muted">SUBTOTAL VENTA</small>
                                <h2 class="mb-0">${money(originalTotal)}</h2>
                                ${promotionDiscount > 0 ? `<div class="text-success mt-1">Promoción: -${money(promotionDiscount)}</div><small class="text-muted">Total final: ${money(invoice.total_amount)}</small>` : ''}
                            </div>

                            <div class="text-end">
                                <small class="text-muted">TOTAL A COBRAR</small>
                                <h2 class="text-success mb-0">${money(balance)}</h2>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex flex-wrap gap-2">
                            ${methodButton('card', 'Tarjeta', isPaid)}
                            ${methodButton('cash', 'Efectivo', isPaid)}
                            ${methodButton('transfer', 'Transferencia', isPaid)}
                            ${methodButton('check', 'Cheque', isPaid)}
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" id="methodBox"></div>
                </div>

                <div class="card mt-3">
                    <div class="card-header"><h5 class="mb-0">Promociones disponibles</h5></div>
                    <div class="card-body" id="promotionsBox">
                        ${invoice.sales_promotion ? `<div class="alert alert-success mb-0"><strong>${invoice.sales_promotion.name}</strong> aplicada · Ahorro ${money(invoice.promotion_discount_amount)}</div>` : renderPromotions()}
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5>Resumen formas de pago</h5>

                        <table class="table table-sm table-bordered mt-3">
                            <thead>
                                <tr>
                                    <th>Medio</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>

                            <tbody>
                                ${payments.map(p => `
                                    <tr>
                                        <td>
                                            ${labelMethod(p.payment_method)}
                                            ${p.card_name ? `<br><small class="text-muted">${p.card_name} ${p.card_plan ?? ''}</small>` : ''}
                                            ${p.provider === 'mercado_pago' ? `<br><small class="${p.status === 'approved' ? 'text-success' : p.status === 'pending' ? 'text-warning' : 'text-danger'}">${labelQrStatus(p.status)}</small>` : ''}
                                        </td>
                                        <td class="text-end">
                                            ${money(
                                                p.payment_method === 'current_account'
                                                    ? p.amount
                                                    : p.total_paid
                                            )}
                                        </td>
                                        <td class="text-center">
                                            ${p.payment_method === 'voucher' && p.voucher_id && !invoice.stock_applied_at
                                                ? `<button type="button" class="btn btn-sm btn-icon btn-outline-danger" title="Quitar y liberar voucher" aria-label="Quitar y liberar voucher" onclick="removeVoucherPayment(${p.id}, this)"><i class="icon-base ri ri-delete-bin-line"></i></button>`
                                                : '-'}
                                        </td>
                                    </tr>
                                `).join('') || `
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">
                                            Sin pagos agregados
                                        </td>
                                    </tr>
                                `}
                            </tbody>
                        </table>

                        <hr>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Saldo</span>
                            <strong>${money(balance)}</strong>
                        </div>

                        ${promotionDiscount > 0 ? `<div class="d-flex justify-content-between mb-2 text-success"><span>Descuento promocional</span><strong>-${money(promotionDiscount)}</strong></div><div class="d-flex justify-content-between mb-2"><span>Importe final</span><strong>${money(invoice.total_amount)}</strong></div>` : ''}

                        <div class="d-flex justify-content-between">
                            <span>Total cobrado</span>
                            <strong>${money(paid)}</strong>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5>Comprobante de venta</h5>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="print_invoice" checked>
                            <label class="form-check-label" for="print_invoice">
                                ${invoice.is_internal_receipt ? 'Imprimir ticket de venta al finalizar' : 'Imprimir factura en formato ticket al finalizar'}
                            </label>
                        </div>

                        <small class="text-muted d-block mb-4">
                            ${invoice.is_internal_receipt
                                ? 'La venta se guardará igualmente aunque esta opción no esté marcada.'
                                : 'La factura se guardará igualmente aunque esta opción no esté marcada.'}
                        </small>

                        <hr>

                        <h5>Ticket de cambio</h5>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="print_exchange_ticket">
                            <label class="form-check-label" for="print_exchange_ticket">
                                Imprimir ticket de cambio
                            </label>
                        </div>

                        <div class="mb-3">
                            <label>Cantidad de tickets</label>
                            <input id="exchange_ticket_quantity" class="form-control" type="number" min="1" value="1">
                        </div>

                        <div class="mb-3">
                            <label>Validez en días</label>
                            <input id="exchange_ticket_valid_days" class="form-control" type="number" min="1" value="30">
                        </div>

                        ${!isPaid ? `<small class="text-warning d-block mb-2"><i class="ri-information-line me-1"></i>Asigná el saldo completo a una forma de pago para finalizar.</small>` : ''}
                        <button class="btn btn-success w-100" onclick="finishSale(this)" ${isPaid ? '' : 'disabled'}>
                            ${isPaid ? 'Finalizar venta' : 'Saldo pendiente'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    renderMethod(balance);
}

function selectMethod(method) {
    selectedMethod = method;
    render();
    loadCompatiblePromotions();
}

function renderMethod(balance) {
    if (balance <= 0.005) {
        methodBox.innerHTML = `
            <div class="alert alert-success mb-0">
                <strong>Pago completo.</strong> La factura no tiene saldo pendiente para agregar otro cobro.
            </div>
        `;
        return;
    }

    if (selectedMethod === 'cash') {
        methodBox.innerHTML = `
            <h5>Efectivo</h5>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>Importe</label>
                    <input class="form-control" id="amount" type="number" value="${balance}">
                </div>
            </div>

            <button class="btn btn-primary fw-semibold" onclick="addPayment()">Agregar pago</button>
        `;
    }

    if (selectedMethod === 'card') {
        methodBox.innerHTML = `
            <h5>Tarjeta</h5>
            <p class="text-muted">Los datos se registran manualmente.</p>
            <div class="row">
                <div class="col-md-3 mb-3"><label>Tipo</label><select class="form-select" id="card_type"><option value="credit">Crédito</option><option value="debit">Débito</option></select></div>
                <div class="col-md-3 mb-3"><label>Tarjeta</label><input class="form-control" id="card_name" placeholder="Visa, Mastercard..."></div>
                <div class="col-md-3 mb-3"><label>Titular</label><input class="form-control" id="card_holder"></div>
                <div class="col-md-3 mb-3"><label>Últimos 4 dígitos</label><input class="form-control" id="last_digits_card" maxlength="4"></div>
                <div class="col-md-3 mb-3"><label>Cuotas</label><input class="form-control" id="card_plan" type="number" min="1" value="1"></div>
                <div class="col-md-3 mb-3"><label>Autorización / cupón</label><input class="form-control" id="coupon_number"></div>
                <div class="col-md-3 mb-3"><label>Importe</label><input class="form-control" id="amount" type="number" min="0.01" step="0.01" value="${balance}"></div>
            </div>
            <button class="btn btn-primary fw-semibold" onclick="addPayment()">Agregar pago</button>
        `;
    }

    if (selectedMethod === 'check') {
        const title = labelMethod(selectedMethod);
        methodBox.innerHTML = `
            <h5>${title}</h5>
            <p class="text-muted">Los datos se registran manualmente.</p>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>Banco</label>
                    <input class="form-control" id="bank_name">
                </div>
                <div class="col-md-4 mb-3">
                    <label>Referencia</label>
                    <input class="form-control" id="reference">
                </div>
                <div class="col-md-4 mb-3">
                    <label>Importe</label>
                    <input class="form-control" id="amount" type="number" min="0.01" step="0.01" value="${balance}">
                </div>
            </div>
            <button class="btn btn-primary fw-semibold" onclick="addPayment()">Agregar pago</button>
        `;
    }

    if (selectedMethod === 'mercado_pago_qr') {
        const pendingPayment = (invoice.payments ?? []).find(payment =>
            payment.provider === 'mercado_pago' && payment.status === 'pending'
        );

        methodBox.innerHTML = `
            <h5>Mercado Pago QR</h5>
            <div class="alert alert-info">
                El cobro se registrará únicamente cuando Mercado Pago confirme la acreditación.
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>Importe</label>
                    <input class="form-control" id="amount" type="number" min="0.01" step="0.01" value="${balance}">
                </div>
            </div>
            ${pendingPayment
                ? `<button class="btn btn-primary" onclick="resumeMercadoPagoQr(${pendingPayment.id})">Ver QR pendiente</button>`
                : `<button class="btn btn-primary" onclick="startMercadoPagoQr(this)">Generar QR</button>`
            }
        `;
    }

    if (selectedMethod === 'transfer') {
        methodBox.innerHTML = `
            <h5>Transferencia</h5>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Banco / cuenta</label>
                    <input class="form-control" id="bank_name">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Referencia</label>
                    <input class="form-control" id="reference">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Importe</label>
                    <input class="form-control" id="amount" type="number" value="${balance}">
                </div>
            </div>

            <button class="btn btn-primary fw-semibold" onclick="addPayment()">Agregar pago</button>
        `;
    }

    if (selectedMethod === 'credit_card') {
        methodBox.innerHTML = `
            <h5>Tarjeta Crédito</h5>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>Tarjeta</label>
                    <select class="form-select" id="card_name" onchange="loadPlans()">
                        <option value="">Seleccione tarjeta...</option>
                        ${Object.keys(cardPlans).map(c => `<option value="${c}">${c}</option>`).join('')}
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label>Plan</label>
                    <select class="form-select" id="card_plan" onchange="calculateCardTotal()">
                        <option value="">Seleccione plan...</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label>Importe</label>
                    <input class="form-control" id="amount" type="number" value="${balance}" oninput="calculateCardTotal()">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Nro. Cupón</label>
                    <input class="form-control" id="coupon_number">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Nro. Lote</label>
                    <input class="form-control" id="lot_number">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Últimos 4 dígitos</label>
                    <input class="form-control" id="last_digits_card" maxlength="4">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Nro. Comercio</label>
                    <input class="form-control" id="trade_number">
                </div>
            </div>

            <div class="alert alert-light border">
                <strong>Recargo:</strong> <span id="surchargeText">$0</span><br>
                <strong>Total a pagar:</strong> <span id="totalText">${money(balance)}</span>
            </div>

            <button class="btn btn-primary fw-semibold" onclick="addPayment()">Agregar pago</button>
        `;
    }

    if (selectedMethod === 'debit_card') {
        methodBox.innerHTML = `
            <h5>Tarjeta Débito</h5>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>Tarjeta</label>
                    <select class="form-select" id="card_name">
                        <option value="Visa">Visa</option>
                        <option value="Cabal">Cabal</option>
                        <option value="Mastercard">Mastercard</option>
                        <option value="Mercado Pago">Mercado Pago</option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label>Plan</label>
                    <input class="form-control" id="card_plan" value="1" readonly>
                </div>

                <div class="col-md-4 mb-3">
                    <label>Importe</label>
                    <input class="form-control" id="amount" type="number" value="${balance}">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Nro. Cupón</label>
                    <input class="form-control" id="coupon_number">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Nro. Lote</label>
                    <input class="form-control" id="lot_number">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Últimos 4 dígitos</label>
                    <input class="form-control" id="last_digits_card" maxlength="4">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Nro. Comercio</label>
                    <input class="form-control" id="trade_number">
                </div>
            </div>

            <button class="btn btn-primary fw-semibold" onclick="addPayment()">Agregar pago</button>
        `;
    }
    if (selectedMethod === 'voucher') {
        methodBox.innerHTML = `
            <h5>Voucher</h5>
            <p class="text-muted">Ingresá el código o escaneá el QR para comprobar su estado.</p>
            <div class="row g-3 align-items-end">
                <div class="col-md-7"><label class="form-label">Código</label><input id="voucher_code" class="form-control text-uppercase" autocomplete="off" placeholder="VCH-..."></div>
                <div class="col-md-5"><button type="button" class="btn btn-outline-primary w-100" onclick="lookupVoucher()"><i class="ri-qr-scan-line me-1"></i> Validar voucher</button></div>
                <div class="col-12" id="voucher_result"></div>
                <input id="amount" type="hidden" value="0">
                <div class="col-12"><button id="voucher_add" class="btn btn-primary fw-semibold" onclick="addPayment()" disabled>Agregar voucher como pago</button></div>
            </div>`;
        document.getElementById('voucher_code').addEventListener('keydown', event => {
            if (event.key === 'Enter') { event.preventDefault(); lookupVoucher(); }
        });
    }
    if (selectedMethod === 'current_account') {

    methodBox.innerHTML = `
        <h5>Cuenta Corriente</h5>

        <div class="alert alert-warning">
            El importe registrado quedará pendiente de cobro y se visualizará en la Cuenta Corriente del cliente.
        </div>

        <div class="row">

            <div class="col-md-4 mb-3">
                <label>Importe</label>
                <input
                    class="form-control"
                    id="amount"
                    type="number"
                    value="${balance}">
            </div>

            <div class="col-md-4 mb-3">
                <label>Vencimiento</label>
                <input
                    class="form-control"
                    id="payment_due_date"
                    type="date">
            </div>

            <div class="col-md-4 mb-3">
                <label>Observaciones</label>
                <input
                    class="form-control"
                    id="notes">
            </div>

        </div>

        <button
            class="btn btn-dark"
            onclick="addPayment()">
            Agregar a Cuenta Corriente
        </button>
    `;
}
}

function loadPlans() {
    const card = document.getElementById('card_name').value;
    const plans = cardPlans[card] ?? [];

    card_plan.innerHTML = '<option value="">Seleccione plan...</option>';

    plans.forEach(plan => {
        card_plan.innerHTML += `
            <option value="${plan.name}" data-recharge="${plan.recharge}">
                ${plan.text}
            </option>
        `;
    });

    calculateCardTotal();
}

function calculateCardTotal() {
    const amount = Number(document.getElementById('amount')?.value || 0);
    const option = document.getElementById('card_plan')?.selectedOptions[0];
    const recharge = Number(option?.dataset?.recharge || 0);

    const surcharge = amount * recharge;
    const total = amount + surcharge;

    if (document.getElementById('surchargeText')) {
        surchargeText.innerText = money(surcharge);
        totalText.innerText = money(total);
    }
}

async function addPayment() {
    const payments = invoice?.payments ?? [];
    const paid = payments.reduce((total, payment) => total + appliedPaymentAmount(payment), 0);
    const balance = Math.max(0, Number(invoice?.total_amount ?? 0) - paid);

    if (balance <= 0.005) {
        alert('La factura ya está completamente pagada.');
        render();
        return;
    }

    const amount = Number(document.getElementById('amount')?.value || 0);

    if (amount <= 0) {
        alert('El importe debe ser mayor a 0.');
        return;
    }

    let payload = {
        payment_method: selectedMethod,
        amount,
        discount_amount: 0,
        surcharge_amount: 0
    };

    if (selectedMethod === 'transfer') {
        payload.bank_name = document.getElementById('bank_name')?.value || null;
        payload.reference = document.getElementById('reference').value;
    }

    if (selectedMethod === 'check') {
        payload.reference = document.getElementById('reference')?.value || null;
        payload.bank_name = document.getElementById('bank_name')?.value || null;
    }

    if (selectedMethod === 'card') {
        const cardType = document.getElementById('card_type')?.value;
        const cardHolder = document.getElementById('card_holder')?.value;
        const lastFour = document.getElementById('last_digits_card')?.value;
        const authorization = document.getElementById('coupon_number')?.value;

        payload.card_name = document.getElementById('card_name')?.value || null;
        payload.card_plan = document.getElementById('card_plan')?.value || '1';
        payload.reference = authorization || null;
        payload.notes = [
            cardType === 'debit' ? 'Débito' : 'Crédito',
            cardHolder && `Titular: ${cardHolder}`,
            lastFour && `Terminación: ${lastFour}`,
            authorization && `Autorización: ${authorization}`,
        ].filter(Boolean).join(' | ') || null;
    }

    if (selectedMethod === 'credit_card') {
        const option = document.getElementById('card_plan').selectedOptions[0];
        const recharge = Number(option?.dataset?.recharge || 0);

        payload.card_name = document.getElementById('card_name').value;
        payload.card_plan = document.getElementById('card_plan').value;
        payload.card_surcharge_percentage = recharge;
        payload.surcharge_amount = amount * recharge;
        payload.coupon_number = document.getElementById('coupon_number').value;
        payload.lot_number = document.getElementById('lot_number').value;
        payload.last_digits_card = document.getElementById('last_digits_card').value;
        payload.trade_number = document.getElementById('trade_number').value;
    }

    if (selectedMethod === 'debit_card') {
        payload.card_name = document.getElementById('card_name').value;
        payload.card_plan = document.getElementById('card_plan').value || '1';
        payload.card_surcharge_percentage = 0;
        payload.surcharge_amount = 0;
        payload.coupon_number = document.getElementById('coupon_number').value;
        payload.lot_number = document.getElementById('lot_number').value;
        payload.last_digits_card = document.getElementById('last_digits_card').value;
        payload.trade_number = document.getElementById('trade_number').value;
    }

    if (selectedMethod === 'current_account') {

        payload.payment_due_date =
            document.getElementById('payment_due_date').value;

        payload.notes =
            document.getElementById('notes').value;
    }

    if (selectedMethod === 'voucher') {
        payload.voucher_code = document.getElementById('voucher_code').value.trim();
    }

    const res = await fetch(`${window.APP_BASE_URL}/api/invoices/${invoiceId}/payments`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json'
        },
        body: JSON.stringify(payload)
    });

    if (res.ok) {
        invoice = await res.json();
        render();
    } else {
        const error = await res.json();
        alert(error.message ?? 'Error al agregar pago');
    }

}

function labelQrStatus(status) {
    return {
        pending: 'Pendiente de acreditación',
        approved: 'Acreditado',
        cancelled: 'Cancelado',
        expired: 'Vencido',
        refunded: 'Reintegrado',
        failed: 'Fallido'
    }[status] ?? status;
}

function mercadoPagoModal() {
    return bootstrap.Modal.getOrCreateInstance(document.getElementById('mercadoPagoQrModal'), {
        backdrop: 'static',
        keyboard: false
    });
}

function showMercadoPagoQr(payment) {
    activeQrPaymentId = payment.id;
    document.getElementById('mercadoPagoQrAmount').textContent = money(payment.amount);
    document.getElementById('mercadoPagoQrImage').src = payment.qr_image ?? '';
    document.getElementById('mercadoPagoQrImage').style.display = payment.qr_image ? 'block' : 'none';
    document.getElementById('mercadoPagoQrStatus').className = 'alert alert-info';
    document.getElementById('mercadoPagoQrStatus').textContent = 'Esperando la confirmación de Mercado Pago…';
    document.getElementById('mercadoPagoQrCancel').disabled = false;
    mercadoPagoModal().show();
    scheduleQrPolling();
}

async function startMercadoPagoQr(button) {
    const amount = Number(document.getElementById('amount')?.value || 0);

    if (amount <= 0) {
        alert('El importe debe ser mayor a 0.');
        return;
    }

    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generando QR...';

    try {
        const response = await fetch(`${window.APP_BASE_URL}/api/invoices/${invoiceId}/mercado-pago-qr`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', Accept: 'application/json'},
            body: JSON.stringify({ amount })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'No se pudo generar el QR.');

        showMercadoPagoQr(data);
        await reloadInvoice();
    } catch (error) {
        alert(error.message);
    } finally {
        button.disabled = false;
        button.innerHTML = 'Generar QR';
    }
}

async function resumeMercadoPagoQr(paymentId) {
    const payment = (invoice.payments ?? []).find(item => Number(item.id) === Number(paymentId));
    showMercadoPagoQr({id: paymentId, amount: payment?.amount, qr_image: null});
    await pollMercadoPagoQr();
}

function scheduleQrPolling() {
    clearTimeout(qrPollTimer);
    qrPollTimer = setTimeout(pollMercadoPagoQr, 3000);
}

async function pollMercadoPagoQr() {
    if (!activeQrPaymentId) return;

    try {
        const response = await fetch(`${window.APP_BASE_URL}/api/invoices/${invoiceId}/mercado-pago-qr/${activeQrPaymentId}`, {
            headers: {Accept: 'application/json'},
            erpSilent: true
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'No se pudo consultar el pago.');

        if (data.qr_image) {
            document.getElementById('mercadoPagoQrImage').src = data.qr_image;
            document.getElementById('mercadoPagoQrImage').style.display = 'block';
        }

        if (data.status === 'approved') {
            clearTimeout(qrPollTimer);
            activeQrPaymentId = null;
            document.getElementById('mercadoPagoQrStatus').className = 'alert alert-success';
            document.getElementById('mercadoPagoQrStatus').textContent = 'Pago acreditado correctamente.';
            document.getElementById('mercadoPagoQrCancel').disabled = true;
            await reloadInvoice();
            setTimeout(() => mercadoPagoModal().hide(), 1200);
            return;
        }

        if (['cancelled', 'expired', 'refunded', 'failed'].includes(data.status)) {
            clearTimeout(qrPollTimer);
            activeQrPaymentId = null;
            document.getElementById('mercadoPagoQrStatus').className = 'alert alert-danger';
            document.getElementById('mercadoPagoQrStatus').textContent = `La orden quedó ${labelQrStatus(data.status).toLowerCase()}.`;
            document.getElementById('mercadoPagoQrCancel').disabled = true;
            await reloadInvoice();
            return;
        }

        scheduleQrPolling();
    } catch (error) {
        document.getElementById('mercadoPagoQrStatus').className = 'alert alert-warning';
        document.getElementById('mercadoPagoQrStatus').textContent = `${error.message} Se volverá a intentar.`;
        scheduleQrPolling();
    }
}

async function cancelMercadoPagoQr() {
    if (!activeQrPaymentId) return;
    const accepted = await window.erpConfirm('¿Querés cancelar esta orden QR?', {
        title: 'Cancelar orden QR',
        confirmButtonText: 'Cancelar orden'
    });
    if (!accepted) return;

    const response = await fetch(`${window.APP_BASE_URL}/api/invoices/${invoiceId}/mercado-pago-qr/${activeQrPaymentId}/cancel`, {
        method: 'POST',
        headers: {Accept: 'application/json'}
    });
    const data = await response.json();
    if (!response.ok) {
        alert(data.message ?? 'No se pudo cancelar la orden.');
        return;
    }

    clearTimeout(qrPollTimer);
    activeQrPaymentId = null;
    mercadoPagoModal().hide();
    await reloadInvoice();
}

async function reloadInvoice() {
    const response = await fetch(`${window.APP_BASE_URL}/api/invoices/${invoiceId}`, {
        headers: {Accept: 'application/json'}
    });
    if (!response.ok) return;
    invoice = await response.json();
    render();
}

async function finishSale(finishButton) {
    const payments = invoice?.payments ?? [];

    if (payments.some(payment => payment.provider === 'mercado_pago' && payment.status === 'pending')) {
        alert('Hay un pago de Mercado Pago pendiente. Confirmalo o cancelalo antes de finalizar la venta.');
        return;
    }

    const appliedPayments = payments.filter(isAppliedPayment);
    const paid = appliedPayments.reduce((total, payment) => total + appliedPaymentAmount(payment), 0);
    const balance = Math.max(0, Number(invoice?.total_amount ?? 0) - paid);

    if (!appliedPayments.length) {
        alert('Agregá al menos una forma de pago antes de finalizar la venta.');
        return;
    }

    if (balance > 0.005) {
        alert(`Todavía queda un saldo pendiente de ${money(balance)}. Completá el pago o asignalo a Cuenta Corriente.`);
        return;
    }

    const printInvoice = document.getElementById('print_invoice')?.checked;
    const printExchangeTicket = document.getElementById('print_exchange_ticket')?.checked;
    const quantity = Number(document.getElementById('exchange_ticket_quantity')?.value || 1);
    const validDays = Number(document.getElementById('exchange_ticket_valid_days')?.value || 30);
    let exchangeTicketPrintUrl = null;

    if (finishButton) {
        finishButton.disabled = true;
        finishButton.innerHTML = invoice?.is_internal_receipt
            ? '<span class="spinner-border spinner-border-sm me-1"></span> Finalizando venta...'
            : '<span class="spinner-border spinner-border-sm me-1"></span> Autorizando ARCA...';
    }

    let finalizeResponse;
    try {
        finalizeResponse = await fetch(`${window.APP_BASE_URL}/api/invoices/${invoiceId}/finalize`, {
            method: 'POST',
            headers: { Accept: 'application/json' }
        });
    } catch (error) {
        if (finishButton) {
            finishButton.disabled = false;
            finishButton.innerHTML = 'Finalizar venta';
        }
        alert(invoice?.is_internal_receipt
            ? 'No se pudo finalizar la venta. Revisá la conexión e intentá nuevamente.'
            : 'No se pudo conectar con ARCA. La venta quedó guardada y podés volver a intentar.');
        return;
    }
    const finalizeData = await finalizeResponse.json().catch(() => ({}));

    if (!finalizeResponse.ok) {
        if (finishButton) {
            finishButton.disabled = false;
            finishButton.innerHTML = 'Finalizar venta';
        }
        alert(finalizeData.message ?? (invoice?.is_internal_receipt
            ? 'No se pudo finalizar el comprobante interno.'
            : 'No se pudo autorizar la factura en ARCA.'));
        return;
    }

    invoice = finalizeData.invoice ?? invoice;
    sessionStorage.removeItem('sales.invoice.current_draft');

    if (printExchangeTicket) {
        const res = await fetch(`${window.APP_BASE_URL}/api/invoices/${invoiceId}/exchange-tickets`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json'
            },
            body: JSON.stringify({
                quantity,
                valid_days: validDays
            })
        });

        if (res.ok) {
            const tickets = await res.json();

            if (tickets.length) {
                exchangeTicketPrintUrl = `${window.APP_BASE_URL}/demo/exchange-tickets/${tickets[0].id}/print`;
            }
        } else {
            const error = await res.json();
            console.log(error);
            if (finishButton) {
                finishButton.disabled = false;
                finishButton.innerHTML = 'Finalizar venta';
            }
            alert(error.message ?? 'Error al generar ticket de cambio.');
            return;
        }
    }

    try {
        if (printInvoice) {
            await window.erpThermalPrinter.printUrl(
                `${window.APP_BASE_URL}/demo/sales/${invoiceId}/ticket`,
                `Factura ${invoice?.full_number ?? invoiceId}`
            );
        }

        if (printInvoice && exchangeTicketPrintUrl) {
            await new Promise(resolve => setTimeout(resolve, 1000));
        }

        if (exchangeTicketPrintUrl) {
            await window.erpThermalPrinter.printUrl(exchangeTicketPrintUrl, `Ticket de cambio ${invoiceId}`);
        }
    } catch (error) {
        alert(error.message);
    }

    location.href = `${window.APP_BASE_URL}/demo/sales/${invoiceId}`;
}

async function removeVoucherPayment(paymentId, button) {
    if (!await window.erpConfirm('¿Querés quitar este pago y volver a dejar disponible el voucher?')) return;
    button.disabled = true;
    try {
        const response = await fetch(`${window.APP_BASE_URL}/api/invoices/${invoiceId}/payments/${paymentId}/voucher`, {
            method: 'DELETE', headers: {Accept: 'application/json'}
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'No se pudo liberar el voucher.');
        invoice = data.invoice;
        render();
    } catch (error) {
        button.disabled = false;
        alert(error.message);
    }
}

async function lookupVoucher() {
    const code = document.getElementById('voucher_code').value.trim();
    const result = document.getElementById('voucher_result');
    const addButton = document.getElementById('voucher_add');
    addButton.disabled = true;
    document.getElementById('amount').value = 0;
    if (!code) { result.innerHTML = '<div class="alert alert-warning mb-0">Ingresá el código del voucher.</div>'; return; }
    let lookupUrl = `${window.APP_BASE_URL}/api/vouchers/lookup/${encodeURIComponent(code)}`;
    try {
        const scannedUrl = new URL(code);
        const voucherId = scannedUrl.pathname.split('/').filter(Boolean).pop();
        if (/^\d+$/.test(voucherId)) lookupUrl = `${window.APP_BASE_URL}/api/vouchers/${voucherId}`;
    } catch (_) {}
    const response = await fetch(lookupUrl, {headers:{Accept:'application/json'}});
    const voucher = await response.json();
    const voucherStatus = voucher.effective_status || voucher.status;
    if (!response.ok || voucherStatus !== 'available') {
        const labels={used:'Este voucher ya fue usado.',reserved:'Este voucher está reservado en otra venta.',disabled:'Este voucher está inhabilitado.',expired:'Este voucher está vencido.'};
        result.innerHTML = `<div class="alert alert-danger mb-0">${labels[voucherStatus] || voucher.message || 'El voucher no está disponible.'}</div>`;
        return;
    }
    document.getElementById('voucher_code').value = voucher.code;
    const balance = Math.max(0, Number(invoice?.total_amount || 0) - (invoice?.payments || []).reduce((total,payment)=>total+appliedPaymentAmount(payment),0));
    const percentage = voucher.value_type === 'percentage';
    let voucherAmount = percentage
        ? Number(invoice?.total_amount || 0) * Number(voucher.amount || 0) / 100
        : Number(voucher.amount || 0);
    if (percentage && voucher.maximum_discount_amount) {
        voucherAmount = Math.min(voucherAmount, Number(voucher.maximum_discount_amount));
    }
    voucherAmount = Math.round(voucherAmount * 100) / 100;
    if (voucherAmount > balance + .005) {
        result.innerHTML = `<div class="alert alert-warning mb-0">${voucher.name}: ${percentage ? `${Number(voucher.amount).toLocaleString('es-AR')}% = ` : ''}${money(voucherAmount)}. Supera el saldo pendiente de ${money(balance)}.</div>`;
        return;
    }
    document.getElementById('amount').value = voucherAmount.toFixed(2);
    result.innerHTML = `<div class="alert alert-success mb-0"><strong>${voucher.name}</strong><br>${percentage ? `${Number(voucher.amount).toLocaleString('es-AR')}% · ` : ''}${money(voucherAmount)} · Disponible${percentage && voucher.maximum_discount_amount ? `<br><small>Tope máximo: ${money(voucher.maximum_discount_amount)}</small>` : ''}</div>`;
    addButton.disabled = false;
}

function renderPromotions() {
    if (!compatiblePromotions.length) return '<div class="text-muted">No hay promociones compatibles con esta venta y medio de pago.</div>';
    return compatiblePromotions.map(p => `<div class="border rounded p-3 mb-2 d-flex justify-content-between align-items-center gap-3"><div><strong>${p.name}</strong><br><small class="text-muted">Ahorro estimado: ${money(p.estimated_discount)} · La aplicación es opcional</small></div><button type="button" class="btn btn-sm btn-primary" onclick="applyPromotion(${p.id})">Aplicar</button></div>`).join('');
}

async function loadCompatiblePromotions() {
    if (!invoice || invoice.sales_promotion_id || (invoice.payments ?? []).some(payment => payment.status === 'approved' || !payment.status)) return;
    const response = await fetch(`${window.APP_BASE_URL}/api/invoices/${invoiceId}/compatible-promotions?payment_methods[]=${encodeURIComponent(selectedMethod)}`);
    if (!response.ok) return;
    compatiblePromotions = await response.json();
    if (document.getElementById('promotionsBox')) promotionsBox.innerHTML = renderPromotions();
}

async function applyPromotion(id) {
    const accepted = await window.erpConfirm('¿Querés aplicar esta promoción a la venta?', { title: 'Aplicar promoción', confirmButtonText: 'Aplicar' });
    if (!accepted) return;
    const response = await fetch(`${window.APP_BASE_URL}/api/invoices/${invoiceId}/promotions/${id}/apply`, { method:'POST', headers:{'Content-Type':'application/json',Accept:'application/json'}, body:JSON.stringify({payment_methods:[selectedMethod]}) });
    const data = await response.json();
    if (!response.ok) { alert(data.message ?? 'No se pudo aplicar la promoción.'); return; }
    invoice = data;
    alert('Promoción aplicada correctamente.');
    render();
}

fetch(`${window.APP_BASE_URL}/api/invoices/${invoiceId}`)
    .then(r => r.json())
    .then(data => {
        invoice = data;
        render();
        loadCompatiblePromotions();
    });
</script>

@endsection
