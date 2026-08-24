{{-- Vista: Cupones de Tarjeta. Muestra la pantalla o componente funcional correspondiente a Cupones de Tarjeta. --}}
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Conciliación de cupones</h4>
        <small class="text-muted">Registrá una liquidación y su acreditación bancaria</small>
    </div>
    <button id="saveButton" class="btn btn-success" type="button"><i class="ri-save-line me-1"></i>Guardar conciliación</button>
</div>

<div class="card mb-4"><div class="card-body"><div class="row g-3">
    <div class="col-md-3"><label class="form-label">Nº liquidación entidad</label><input id="settlement_number" class="form-control" maxlength="255"></div>
    <div class="col-md-2"><label class="form-label">Fecha documento</label><input id="issue_date" type="date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
    <div class="col-md-2"><label class="form-label">Fecha acreditación</label><input id="accreditation_date" type="date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
    <div class="col-md-2"><label class="form-label">Banco</label><select id="bank_id" class="form-select" required><option value="">Seleccionar...</option></select></div>
    <div class="col-md-3"><label class="form-label">Cuenta acreditada</label><select id="bank_account_id" class="form-select" required><option value="">Seleccione banco...</option></select></div>
    <div class="col-12"><label class="form-label">Observaciones</label><textarea id="notes" class="form-control" rows="2" maxlength="2000"></textarea></div>
</div></div></div>

<div class="row g-4">
    <div class="col-xl-9">
        <h5>Filtros</h5>
        <div class="card mb-4"><div class="card-body"><div class="row g-3 align-items-end">
            <div class="col-md-3"><label class="form-label">Tarjeta</label><input id="filter_card" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Lote</label><input id="filter_lot" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Plan</label><input id="filter_plan" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Cupón</label><input id="filter_coupon" class="form-control"></div>
            <div class="col-md-3"><button id="clearFilters" class="btn btn-outline-secondary" type="button">Limpiar filtros</button></div>
        </div></div></div>

        <div class="card">
            <div class="card-header d-flex justify-content-between"><strong>Cupones pendientes</strong><span id="selectedCount" class="badge bg-label-primary">0 seleccionados</span></div>
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead><tr><th><input type="checkbox" id="checkAll"></th><th>Tarjeta</th><th>Cupón</th><th>Lote</th><th>Plan</th><th class="text-end">Bruto</th><th class="text-end">Comisión</th><th class="text-end">Neto cupón</th><th>Fecha estimada</th><th>Cliente</th></tr></thead>
                <tbody id="couponRows"><tr><td colspan="10" class="text-center py-4 text-muted">Cargando...</td></tr></tbody>
            </table></div>
        </div>
    </div>

    <div class="col-xl-3">
        <div class="card position-sticky" style="top:90px"><div class="card-body">
            <h5>Resumen de liquidación</h5><hr>
            <div class="d-flex justify-content-between mb-2"><span>Importe bruto</span><strong id="grossAmount">$0</strong></div>
            <div class="d-flex justify-content-between mb-3"><span>Comisiones</span><strong id="commissionAmount" class="text-danger">$0</strong></div>
            <label class="form-label">Retenciones</label><input id="withholding_amount" type="number" min="0" step="0.01" class="form-control mb-2" value="0">
            <input id="withholding_description" class="form-control form-control-sm mb-3" placeholder="Detalle de retenciones">
            <label class="form-label">Otros descuentos</label><input id="other_discount_amount" type="number" min="0" step="0.01" class="form-control mb-2" value="0">
            <input id="other_discount_description" class="form-control form-control-sm mb-3" placeholder="Detalle de otros descuentos">
            <hr><div class="d-flex justify-content-between align-items-center"><span class="fw-semibold">Importe neto</span><h4 id="netAmount" class="text-success mb-0">$0</h4></div>
            <small class="text-muted d-block mt-3">Este importe generará automáticamente el movimiento bancario.</small>
        </div></div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h5 class="mb-0">Conciliaciones registradas</h5></div>
    <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Documento</th><th>Liquidación</th><th>Fecha</th><th>Banco / cuenta</th><th>Cupones</th><th class="text-end">Bruto</th><th class="text-end">Neto</th><th>Movimiento</th></tr></thead><tbody id="historyRows"></tbody></table></div>
</div>

<script>
let coupons = [], accounts = [], visibleCoupons = [];
const selectedIds = new Set();
const money = value => Number(value ?? 0).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });

function renderCoupons() {
    const card = filter_card.value.toLowerCase(), lot = filter_lot.value.toLowerCase(), plan = filter_plan.value.toLowerCase(), coupon = filter_coupon.value.toLowerCase();
    visibleCoupons = coupons.filter(item => String(item.credit_card ?? '').toLowerCase().includes(card) && String(item.lot_number ?? '').toLowerCase().includes(lot) && String(item.credit_card_plan ?? '').toLowerCase().includes(plan) && String(item.coupon_number ?? '').toLowerCase().includes(coupon));
    couponRows.innerHTML = visibleCoupons.length ? visibleCoupons.map(item => `<tr>
        <td><input type="checkbox" class="coupon-check" value="${item.id}" ${selectedIds.has(item.id) ? 'checked' : ''}></td>
        <td><strong>${item.credit_card ?? '-'}</strong><br><small>${item.credit_card_type ?? ''}</small></td><td>${item.coupon_number ?? '-'}</td><td>${item.lot_number ?? '-'}</td><td>${item.credit_card_plan ?? '-'}</td>
        <td class="text-end">${money(item.coupon_amount)}</td><td class="text-end text-danger">${money(item.commission)}</td><td class="text-end fw-semibold">${money(Number(item.coupon_amount) - Number(item.commission))}</td>
        <td class="text-nowrap">${formatDateTime(item.expected_date)}</td><td>${item.customer_name ?? '-'}</td></tr>`).join('') : '<tr><td colspan="10" class="text-center py-4 text-muted">No hay cupones pendientes</td></tr>';
    document.querySelectorAll('.coupon-check').forEach(input => input.addEventListener('change', () => { input.checked ? selectedIds.add(Number(input.value)) : selectedIds.delete(Number(input.value)); calculate(); }));
    checkAll.checked = visibleCoupons.length > 0 && visibleCoupons.every(item => selectedIds.has(item.id));
}

function calculate() {
    const selected = coupons.filter(item => selectedIds.has(item.id));
    const gross = selected.reduce((sum, item) => sum + Number(item.coupon_amount), 0);
    const commissions = selected.reduce((sum, item) => sum + Number(item.commission), 0);
    const net = gross - commissions - Number(withholding_amount.value || 0) - Number(other_discount_amount.value || 0);
    grossAmount.textContent = money(gross); commissionAmount.textContent = money(commissions); netAmount.textContent = money(net); selectedCount.textContent = `${selected.length} seleccionados`;
    netAmount.classList.toggle('text-danger', net < 0); netAmount.classList.toggle('text-success', net >= 0);
}

function renderAccounts() {
    const bank = Number(bank_id.value);
    const filtered = accounts.filter(account => Number(account.bank_id) === bank);
    bank_account_id.innerHTML = '<option value="">Seleccionar cuenta...</option>' + filtered.map(account => `<option value="${account.id}">${account.bank_account_type_text ?? 'Cuenta'} ${account.account_number ?? ''} · ${account.currency_name ?? ''}</option>`).join('');
}

async function loadData() {
    const [couponResponse, optionResponse, historyResponse] = await Promise.all([fetch(`${window.APP_BASE_URL}/api/card-coupon-reconciliations/pending-coupons`), fetch(`${window.APP_BASE_URL}/api/card-coupon-reconciliations/options`), fetch(`${window.APP_BASE_URL}/api/card-coupon-reconciliations`)]);
    coupons = await couponResponse.json(); const options = await optionResponse.json(); const history = await historyResponse.json(); accounts = options.accounts;
    bank_id.innerHTML = '<option value="">Seleccionar...</option>' + options.banks.map(bank => `<option value="${bank.id}">${bank.name}</option>`).join('');
    historyRows.innerHTML = (history.data ?? []).map(item => `<tr><td><a href="${window.APP_BASE_URL}/demo/finance/card-coupons/reconciliations/${item.id}"><strong>${item.number}</strong></a></td><td>${item.settlement_number ?? '-'}</td><td>${formatDateTime(item.issue_date)}</td><td>${item.bank?.name ?? item.bank_account?.bank_name ?? '-'}<br><small>${item.bank_account?.account_number ?? ''}</small></td><td>${item.items_count}</td><td class="text-end">${money(item.gross_amount)}</td><td class="text-end fw-semibold">${money(item.net_amount)}</td><td>${item.bank_movement_id ? `#${item.bank_movement_id}` : '-'}</td></tr>`).join('') || '<tr><td colspan="8" class="text-center py-4 text-muted">Sin conciliaciones registradas</td></tr>';
    renderCoupons(); calculate();
}

['filter_card','filter_lot','filter_plan','filter_coupon'].forEach(id => document.getElementById(id).addEventListener('input', renderCoupons));
clearFilters.addEventListener('click', () => { ['filter_card','filter_lot','filter_plan','filter_coupon'].forEach(id => document.getElementById(id).value = ''); renderCoupons(); });
checkAll.addEventListener('change', () => { visibleCoupons.forEach(item => checkAll.checked ? selectedIds.add(item.id) : selectedIds.delete(item.id)); renderCoupons(); calculate(); });
bank_id.addEventListener('change', renderAccounts); withholding_amount.addEventListener('input', calculate); other_discount_amount.addEventListener('input', calculate);

saveButton.addEventListener('click', async () => {
    if (!selectedIds.size) return alert('Seleccioná al menos un cupón.');
    if (!bank_id.value || !bank_account_id.value) return alert('Seleccioná el banco y la cuenta acreditada.');
    saveButton.disabled = true;
    try {
        const response = await fetch(`${window.APP_BASE_URL}/api/card-coupon-reconciliations`, { method:'POST', headers:{'Content-Type':'application/json', Accept:'application/json'}, body:JSON.stringify({
            coupon_ids:[...selectedIds], settlement_number:settlement_number.value || null, issue_date:issue_date.value, accreditation_date:accreditation_date.value,
            bank_id:Number(bank_id.value), bank_account_id:Number(bank_account_id.value), withholding_amount:Number(withholding_amount.value || 0), withholding_description:withholding_description.value || null,
            other_discount_amount:Number(other_discount_amount.value || 0), other_discount_description:other_discount_description.value || null, notes:notes.value || null
        })});
        const data = await response.json(); if (!response.ok) throw new Error(data.message + (data.errors ? '\n' + Object.values(data.errors).flat().join('\n') : ''));
        alert(`${data.message}\nDocumento: ${data.reconciliation.number}\nMovimiento bancario: #${data.reconciliation.bank_movement_id}`); window.location.reload();
    } catch (error) { alert(error.message); } finally { saveButton.disabled = false; }
});

loadData().catch(error => { couponRows.innerHTML = `<tr><td colspan="10" class="text-danger text-center py-4">${error.message}</td></tr>`; });
</script>
@endsection
