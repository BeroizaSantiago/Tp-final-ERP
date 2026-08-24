{{-- Vista: Cupones de Tarjeta. Muestra la pantalla o componente funcional correspondiente a Cupones de Tarjeta. --}}
@extends('layouts.app')
@section('content')
<a href="{{ url('/demo/finance/card-coupons/reconciliation') }}" class="btn btn-secondary mb-4">Volver</a>
<div id="document"><div class="text-center py-5">Cargando conciliación...</div></div>
<script>
const reconciliationId = @json($reconciliationId);
const money = value => Number(value ?? 0).toLocaleString('es-AR', { style:'currency', currency:'ARS' });
fetch(`${window.APP_BASE_URL}/api/card-coupon-reconciliations/${reconciliationId}`).then(async response => { const data = await response.json(); if (!response.ok) throw new Error(data.message ?? 'No se pudo cargar.'); return data; }).then(item => {
document.getElementById('document').innerHTML = `
<div class="d-flex justify-content-between mb-4"><div><h3>${item.number}</h3><span class="text-muted">Liquidación ${item.settlement_number ?? '-'}</span></div><span class="badge bg-success fs-6 align-self-start">${item.status}</span></div>
<div class="card mb-4"><div class="card-body"><div class="row g-3">
<div class="col-md-3"><small>Fecha documento</small><div class="fw-semibold">${formatDateTime(item.issue_date)}</div></div><div class="col-md-3"><small>Acreditación</small><div class="fw-semibold">${formatDateTime(item.accreditation_date)}</div></div>
<div class="col-md-3"><small>Banco</small><div class="fw-semibold">${item.bank?.name ?? item.bank_account?.bank_name ?? '-'}</div></div><div class="col-md-3"><small>Cuenta</small><div class="fw-semibold">${item.bank_account?.account_number ?? '-'}</div></div>
<div class="col-md-3"><small>Movimiento bancario</small><div><a href="${window.APP_BASE_URL}/demo/finance/bank-movements/${item.bank_movement_id}">#${item.bank_movement_id}</a></div></div><div class="col-md-3"><small>Creado por</small><div>${item.created_by_user?.name ?? '-'}</div></div><div class="col-md-6"><small>Observaciones</small><div>${item.notes ?? '-'}</div></div>
</div></div></div>
<div class="row g-3 mb-4">${[['Bruto',item.gross_amount,''],['Comisiones',item.commission_amount,'text-danger'],['Retenciones',item.withholding_amount,'text-danger'],['Otros descuentos',item.other_discount_amount,'text-danger'],['Neto acreditado',item.net_amount,'text-success']].map(value => `<div class="col"><div class="card h-100"><div class="card-body"><small>${value[0]}</small><h4 class="${value[2]}">${money(value[1])}</h4></div></div></div>`).join('')}</div>
<div class="card"><div class="card-header"><h5 class="mb-0">Cupones incluidos</h5></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Tarjeta</th><th>Cupón</th><th>Lote</th><th>Cliente</th><th class="text-end">Bruto</th><th class="text-end">Comisión</th><th class="text-end">Neto</th></tr></thead><tbody>
${item.items.map(detail => `<tr><td>${detail.coupon?.credit_card ?? '-'}</td><td>${detail.coupon?.coupon_number ?? '-'}</td><td>${detail.coupon?.lot_number ?? '-'}</td><td>${detail.coupon?.customer_name ?? '-'}</td><td class="text-end">${money(detail.coupon_amount)}</td><td class="text-end">${money(detail.commission_amount)}</td><td class="text-end fw-semibold">${money(detail.net_amount)}</td></tr>`).join('')}</tbody></table></div></div>`;
}).catch(error => document.getElementById('document').innerHTML = `<div class="alert alert-danger">${error.message}</div>`);
</script>
@endsection
