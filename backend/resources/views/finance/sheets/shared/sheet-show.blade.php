{{-- Vista: Planillas de Caja. Muestra la pantalla o componente funcional correspondiente a Planillas de Caja. --}}
@extends('layouts.app')

@section('content')
<a href="{{ $backUrl }}" class="btn btn-secondary mb-4">Volver</a>

<div id="box"><div class="text-center py-5">Cargando...</div></div>

<div class="modal fade" id="closeCashModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="closeCashForm">
                <div class="modal-header">
                    <h5 class="modal-title">Cerrar caja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">Ingresá los importes contados. Si existe una diferencia, la observación será obligatoria.</div>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Forma de pago</th><th class="text-end">Teórico</th><th style="width: 210px">Contado</th></tr></thead>
                            <tbody>
                                <tr><td>Efectivo</td><td class="text-end" id="theoreticalCash">$0</td><td><input class="form-control form-control-sm close-count" id="counted_cash" type="number" min="0" step="0.01" required></td></tr>
                                <tr><td>Tarjeta de crédito</td><td class="text-end" id="theoreticalCredit">$0</td><td><input class="form-control form-control-sm close-count" id="counted_credit_card" type="number" min="0" step="0.01" value="0"></td></tr>
                                <tr><td>Tarjeta de débito</td><td class="text-end" id="theoreticalDebit">$0</td><td><input class="form-control form-control-sm close-count" id="counted_debit_card" type="number" min="0" step="0.01" value="0"></td></tr>
                                <tr><td>Transferencias</td><td class="text-end" id="theoreticalTransfer">$0</td><td><input class="form-control form-control-sm close-count" id="counted_transfer" type="number" min="0" step="0.01" value="0"></td></tr>
                                <tr><td>Cheques</td><td class="text-end" id="theoreticalChecks">$0</td><td><input class="form-control form-control-sm close-count" id="counted_checks" type="number" min="0" step="0.01" value="0"></td></tr>
                            </tbody>
                            <tfoot><tr class="fw-bold"><td>Total</td><td class="text-end" id="theoreticalTotal">$0</td><td id="countedTotal">$0</td></tr></tfoot>
                        </table>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label" for="leave_in_cash">Efectivo que queda en caja</label>
                            <input class="form-control" id="leave_in_cash" type="number" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="closing_observation">Observación</label>
                            <textarea class="form-control" id="closing_observation" rows="2" maxlength="2000"></textarea>
                        </div>
                    </div>
                    <div id="closeCashError" class="alert alert-danger mt-3 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="confirmCloseCash">Confirmar cierre</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const apiUrl = @json($apiUrl);
const title = @json($title);
let currentSheet = null;
let closingTheoretical = {};

function money(value) {
    return Number(value ?? 0).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS'
    });
}

function dateTime(value) {
    return formatDateTime(value);
}

function paymentName(value) {
    return ({
        cash: 'Efectivo',
        credit_card: 'Tarjeta de crédito',
        debit_card: 'Tarjeta de débito',
        transfer: 'Transferencia',
        current_account: 'Cuenta corriente',
        mercado_pago_qr: 'Mercado Pago QR',
        voucher: 'Voucher'
    })[value] ?? value ?? '-';
}

fetch(apiUrl, { headers: { Accept: 'application/json' } })
    .then(async response => {
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'No se pudo cargar la planilla.');
        return data;
    })
    .then(sheet => {
        currentSheet = sheet;
        const movements = (sheet.movements ?? []).filter(m => m.status !== 'voided');
        const reversedMovementIds = new Set(movements.map(m => Number(m.reversal_of_movement_id)).filter(Boolean));
        const effectiveMovements = movements.filter(m => !m.reversal_of_movement_id && !reversedMovementIds.has(Number(m.id)));
        const income = effectiveMovements.filter(m => m.movement_type === 'income');
        const totalSold = income.reduce((sum, m) => sum + Number(m.amount), 0);
        const saleCount = new Set(income.map(m => m.invoice_id).filter(Boolean)).size;
        const average = saleCount ? totalSold / saleCount : 0;
        const cashBalance = Number(sheet.opening_cash_amount ?? 0) + effectiveMovements.reduce((sum, m) => {
            if (!m.affects_cash_balance) return sum;
            return sum + (m.movement_type === 'expense' ? -Number(m.amount) : Number(m.amount));
        }, 0);

        const netByMethods = methods => effectiveMovements
            .filter(m => methods.includes(m.payment_method))
            .reduce((sum, m) => sum + (m.movement_type === 'expense' ? -Number(m.amount) : Number(m.amount)), 0);

        closingTheoretical = {
            cash: Number(sheet.opening_cash_amount ?? 0) + netByMethods(['cash']),
            credit: netByMethods(['credit_card']),
            debit: netByMethods(['debit_card']),
            transfer: netByMethods(['transfer']),
            mercado_pago_qr: netByMethods(['mercado_pago_qr']),
            checks: netByMethods(['check', 'cheque', 'third_party_check', 'echeq'])
        };
        closingTheoretical.total = Object.values(closingTheoretical).reduce((sum, value) => sum + value, 0);

        const byPayment = effectiveMovements.reduce((groups, movement) => {
            const key = movement.payment_method ?? 'other';
            groups[key] ??= { count: 0, total: 0 };
            groups[key].count++;
            groups[key].total += Number(movement.amount);
            return groups;
        }, {});

        const paymentRows = Object.entries(byPayment).length
            ? Object.entries(byPayment).map(([method, values]) => `
                <tr>
                    <td>${paymentName(method)}</td>
                    <td class="text-end">${values.count}</td>
                    <td class="text-end fw-semibold">${money(values.total)}</td>
                </tr>`).join('')
            : '<tr><td colspan="3" class="text-center text-muted py-4">Sin movimientos registrados</td></tr>';

        const movementRows = movements.length
            ? movements.map(movement => {
                const isExpense = movement.movement_type === 'expense';
                return `<tr>
                    <td>${dateTime(movement.created_at)}</td>
                    <td><span class="badge ${isExpense ? 'bg-label-danger' : 'bg-label-success'}">${isExpense ? 'Egreso' : 'Ingreso'}</span></td>
                    <td>${movement.document_number ?? movement.invoice?.full_number ?? '-'}</td>
                    <td>${movement.description ?? '-'}</td>
                    <td>${paymentName(movement.payment_method)}</td>
                    <td class="text-end">${isExpense ? '-' : money(movement.amount)}</td>
                    <td class="text-end">${isExpense ? money(movement.amount) : '-'}</td>
                </tr>`;
            }).join('')
            : '<tr><td colspan="7" class="text-center py-5 text-muted">Todavía no existen movimientos asociados a esta planilla.</td></tr>';

        box.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="mb-1">${title} <span class="badge bg-label-primary">Nº ${sheet.number ?? ''}</span></h3>
                    <small class="text-muted">${sheet.cash_box_name ?? ''}</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    ${sheet.status_name === 'Abierta' ? '<button class="btn btn-danger" type="button" onclick="openCloseCashModal()"><i class="ri-lock-line me-1"></i>Cerrar caja</button>' : ''}
                    <span class="badge ${sheet.status_name === 'Abierta' ? 'bg-success' : 'bg-secondary'} fs-6">${sheet.status_name ?? ''}</span>
                </div>
            </div>

            <div class="card mb-4"><div class="card-body"><div class="row g-3">
                <div class="col-md-3"><strong>Caja</strong><br>${sheet.cash_box_name ?? '-'}</div>
                <div class="col-md-2"><strong>Cajero</strong><br>${sheet.user?.name ?? sheet.cashier_name ?? '-'}</div>
                <div class="col-md-2"><strong>Punto de venta</strong><br>${sheet.pos_name ?? '-'}</div>
                <div class="col-md-2"><strong>Apertura</strong><br>${dateTime(sheet.opening_date)}</div>
                <div class="col-md-3"><strong>Cierre</strong><br>${sheet.closing_date ? dateTime(sheet.closing_date) : 'Caja abierta'}</div>
            </div></div></div>

            <div class="row g-3 mb-4">
                <div class="col-md-3"><div class="card h-100"><div class="card-body text-center"><h3>${money(totalSold)}</h3><small class="text-muted">Total cobrado</small></div></div></div>
                <div class="col-md-3"><div class="card h-100"><div class="card-body text-center"><h3>${saleCount}</h3><small class="text-muted">Ventas</small></div></div></div>
                <div class="col-md-3"><div class="card h-100"><div class="card-body text-center"><h3>${money(average)}</h3><small class="text-muted">Ticket promedio</small></div></div></div>
                <div class="col-md-3"><div class="card h-100"><div class="card-body text-center"><h3>${money(cashBalance)}</h3><small class="text-muted">Saldo efectivo</small></div></div></div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Resumen por forma de pago</h5></div>
                <div class="table-responsive"><table class="table table-hover mb-0">
                    <thead><tr><th>Forma de pago</th><th class="text-end">Operaciones</th><th class="text-end">Total</th></tr></thead>
                    <tbody>${paymentRows}</tbody>
                </table></div>
            </div>

            ${sheet.status_name === 'Cerrada' ? `
                <div class="card border-success mb-4"><div class="card-body">
                    <h5>Resumen del cierre</h5>
                    <div class="row g-3">
                        <div class="col-md-3"><small class="text-muted">Teórico</small><div class="fw-bold">${money(sheet.theoretical_total)}</div></div>
                        <div class="col-md-3"><small class="text-muted">Contado</small><div class="fw-bold">${money(sheet.counted_total)}</div></div>
                        <div class="col-md-3"><small class="text-muted">Diferencia</small><div class="fw-bold ${Number(sheet.closing_difference) ? 'text-danger' : 'text-success'}">${money(sheet.closing_difference)}</div></div>
                        <div class="col-md-3"><small class="text-muted">Queda en caja</small><div class="fw-bold">${money(sheet.leave_in_cash)}</div></div>
                    </div>
                </div></div>` : ''}

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Movimientos</h5></div>
                <div class="table-responsive"><table class="table table-hover mb-0">
                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Comprobante</th><th>Concepto</th><th>Forma de pago</th><th class="text-end">Ingreso</th><th class="text-end">Egreso</th></tr></thead>
                    <tbody>${movementRows}</tbody>
                </table></div>
            </div>`;
    })
    .catch(error => {
        box.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    });

function openCloseCashModal() {
    theoreticalCash.textContent = money(closingTheoretical.cash);
    theoreticalCredit.textContent = money(closingTheoretical.credit);
    theoreticalDebit.textContent = money(closingTheoretical.debit);
    theoreticalTransfer.textContent = money(closingTheoretical.transfer);
    theoreticalChecks.textContent = money(closingTheoretical.checks);
    theoreticalTotal.textContent = money(closingTheoretical.total);

    counted_cash.value = closingTheoretical.cash.toFixed(2);
    counted_credit_card.value = closingTheoretical.credit.toFixed(2);
    counted_debit_card.value = closingTheoretical.debit.toFixed(2);
    counted_transfer.value = closingTheoretical.transfer.toFixed(2);
    counted_checks.value = closingTheoretical.checks.toFixed(2);
    updateCountedTotal();
    closeCashError.classList.add('d-none');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('closeCashModal')).show();
}

function updateCountedTotal() {
    const total = [...document.querySelectorAll('.close-count')]
        .reduce((sum, input) => sum + Number(input.value || 0), 0);
    countedTotal.textContent = money(total);
}

document.querySelectorAll('.close-count').forEach(input => input.addEventListener('input', updateCountedTotal));

closeCashForm.addEventListener('submit', async event => {
    event.preventDefault();
    closeCashError.classList.add('d-none');
    confirmCloseCash.disabled = true;

    const payload = {
        counted_cash: Number(counted_cash.value || 0),
        counted_credit_card: Number(counted_credit_card.value || 0),
        counted_debit_card: Number(counted_debit_card.value || 0),
        counted_transfer: Number(counted_transfer.value || 0),
        counted_checks: Number(counted_checks.value || 0),
        leave_in_cash: Number(leave_in_cash.value || 0),
        closing_observation: closing_observation.value.trim() || null
    };

    try {
        const response = await fetch(`${apiUrl}/close`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message ?? 'No se pudo cerrar la caja.');
        bootstrap.Modal.getInstance(document.getElementById('closeCashModal'))?.hide();
        window.location.reload();
    } catch (error) {
        closeCashError.textContent = error.message;
        closeCashError.classList.remove('d-none');
    } finally {
        confirmCloseCash.disabled = false;
    }
});
</script>
@endsection
