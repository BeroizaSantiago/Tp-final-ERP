{{-- Vista: Listado de Tesorería. Muestra la consulta principal y las acciones disponibles de Tesorería. --}}
@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="mb-1">Tesorería</h4><small class="text-muted">Centro financiero de la sucursal</small></div>
    <div class="d-flex gap-2 align-items-center">
        <select id="treasurySelector" class="form-select d-none" style="min-width:220px"></select>
        <a id="openTreasuryButton" href="{{ url('/demo/finance/cash-sheets/create') }}" class="btn btn-primary d-none">Abrir Tesorería</a>
    </div>
</div>

<div id="treasuryAlert"></div>

<div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-3 mb-4">
    <div class="col"><div class="card h-100"><div class="card-body text-center"><small class="text-muted">Saldo actual</small><h3 id="currentBalance" class="mb-0">$0</h3></div></div></div>
    <div class="col"><div class="card h-100"><div class="card-body text-center"><small class="text-muted">Total de ingresos</small><h3 id="totalIncome" class="text-success mb-0">$0</h3></div></div></div>
    <div class="col"><div class="card h-100"><div class="card-body text-center"><small class="text-muted">Total de egresos</small><h3 id="totalExpense" class="text-danger mb-0">$0</h3></div></div></div>
    <div class="col"><div class="card h-100"><div class="card-body text-center"><small class="text-muted">Cierres recibidos</small><h3 id="closuresCount" class="mb-0">0</h3></div></div></div>
    <div class="col"><div class="card h-100"><div class="card-body text-center"><small class="text-muted">Ventas recibidas</small><h3 id="totalSales" class="mb-0">0</h3></div></div></div>
</div>

<div class="card mb-4"><div class="card-body"><div class="row g-3">
    <div class="col-md-3"><small class="text-muted">Tesorería</small><div id="treasuryName" class="fw-semibold">-</div></div>
    <div class="col-md-3"><small class="text-muted">Sucursal</small><div id="branchName" class="fw-semibold">-</div></div>
    <div class="col-md-2"><small class="text-muted">Estado</small><div id="treasuryStatus">-</div></div>
    <div class="col-md-2"><small class="text-muted">Apertura</small><div id="openingDate" class="fw-semibold">-</div></div>
    <div class="col-md-2"><small class="text-muted">Responsable</small><div id="responsibleUser" class="fw-semibold">-</div></div>
</div></div></div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Cierres de caja recibidos</h5></div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>Fecha</th><th>Caja de origen</th><th>Planilla</th><th>Responsable</th><th class="text-end">Importe recibido</th><th class="text-end">Detalle</th></tr></thead>
        <tbody id="closureRows"><tr><td colspan="6" class="text-center py-4 text-muted">Cargando...</td></tr></tbody>
    </table></div>
</div>

<script>
const money = value => Number(value ?? 0).toLocaleString('es-AR', { style:'currency', currency:'ARS' });

async function loadTreasury(cashBoxId = null) {
    const response = await fetch(`${window.APP_BASE_URL}/api/treasury-sheets` + (cashBoxId ? `?cash_box_id=${cashBoxId}` : ''));
    const data = await response.json();
    const sheet = data.treasury, summary = data.summary;

    if (data.treasuries.length > 1) {
        treasurySelector.classList.remove('d-none');
        treasurySelector.innerHTML = data.treasuries.map(box => `<option value="${box.id}" ${sheet?.cash_box_id === box.id ? 'selected' : ''}>${box.branch_name ?? 'Sucursal'} · ${box.name}</option>`).join('');
    }

    currentBalance.textContent = money(summary.current_balance); totalIncome.textContent = money(summary.total_income); totalExpense.textContent = money(summary.total_expense); closuresCount.textContent = summary.closures_received; totalSales.textContent = summary.total_sales ?? 0;

    if (!sheet) {
        treasuryAlert.innerHTML = '<div class="alert alert-warning">No existe una planilla de Tesorería abierta. Mientras permanezca cerrada no podrán abrirse nuevas cajas.</div>';
        openTreasuryButton.classList.remove('d-none');
        closureRows.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No hay una Tesorería disponible</td></tr>';
        return;
    }

    const isOpen = sheet.status_name === 'Abierta' && !sheet.closing_date;
    treasuryAlert.innerHTML = isOpen ? '' : '<div class="alert alert-warning">La Tesorería está cerrada. No se pueden abrir nuevas cajas hasta volver a abrirla.</div>';
    openTreasuryButton.classList.toggle('d-none', isOpen);
    treasuryName.textContent = sheet.cash_box_name ?? sheet.cash_box?.name ?? '-'; branchName.textContent = sheet.branch_name ?? sheet.cash_box?.branch_name ?? '-';
    treasuryStatus.innerHTML = `<span class="badge ${isOpen ? 'bg-success' : 'bg-secondary'}">${sheet.status_name ?? '-'}</span>`;
    openingDate.textContent = formatDateTime(sheet.opening_date); responsibleUser.textContent = sheet.user?.name ?? sheet.cashier_name ?? '-';

    closureRows.innerHTML = data.closures.length ? data.closures.map(item => `<tr>
        <td class="text-nowrap">${formatDateTime(item.created_at)}</td><td><strong>${item.origin_cash_box?.name ?? item.origin_cash_sheet?.cash_box_name ?? '-'}</strong></td>
        <td>#${item.origin_cash_sheet?.number ?? item.origin_cash_sheet_id}</td><td>${item.origin_cash_sheet?.closed_by_user?.name ?? item.user?.name ?? '-'}</td>
        <td class="text-end fw-semibold">${money(item.amount)}</td><td class="text-end"><a class="btn btn-sm btn-primary" href="${window.APP_BASE_URL}/demo/finance/cash-sheets/${item.origin_cash_sheet_id}">Ver cierre</a></td></tr>`).join('') : '<tr><td colspan="6" class="text-center py-4 text-muted">Todavía no se recibieron cierres de caja</td></tr>';
}

treasurySelector.addEventListener('change', () => loadTreasury(treasurySelector.value));
loadTreasury().catch(error => treasuryAlert.innerHTML = `<div class="alert alert-danger">${error.message}</div>`);
</script>
@endsection
