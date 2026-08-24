@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div><h4 class="mb-1">Nueva Nota de Crédito</h4><small class="text-muted">Devolución total o parcial vinculada a una factura finalizada</small></div>
    <x-action-button action="back" :href="url('/demo/sales/credit-notes')" />
</div>

<div id="alertBox"></div>
<form id="creditNoteForm">
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-1">Comprobante original</h5><small class="text-muted">Buscá por número de factura o cliente.</small></div>
        <div class="card-body"><div class="row g-3">
            <div class="col-lg-6"><label class="form-label" for="source_invoice_id">Factura</label><select id="source_invoice_id" class="form-select" required data-remote-url="{{ url('/api/credit-notes/source-invoices') }}" data-remote-placeholder="Buscar factura o cliente..." data-remote-minimum="1"><option value=""></option></select></div>
            <div class="col-md-3"><label class="form-label" for="issue_date">Fecha</label><input id="issue_date" type="date" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label" for="reason_id">Motivo</label><select id="reason_id" class="form-select" required><option value="">Seleccionar...</option></select></div>
        </div></div>
    </div>

    <div id="invoiceSummary" class="card mb-4 d-none"><div class="card-body"><div class="row g-3">
        <div class="col-md-4"><small class="text-muted d-block">Cliente</small><strong id="customerName">—</strong></div>
        <div class="col-md-3"><small class="text-muted d-block">Factura</small><strong id="invoiceNumber">—</strong></div>
        <div class="col-md-2"><small class="text-muted d-block">Fecha</small><strong id="invoiceDate">—</strong></div>
        <div class="col-md-3 text-md-end"><small class="text-muted d-block">Total original</small><strong id="originalTotal" class="fs-5">—</strong></div>
    </div></div></div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center"><div><h5 class="mb-1">Productos a acreditar</h5><small class="text-muted">Ingresá únicamente la cantidad que vuelve. El máximo descuenta devoluciones anteriores.</small></div><button id="selectAllButton" type="button" class="btn btn-outline-primary btn-sm" disabled><i class="ri-checkbox-multiple-line me-1"></i> Devolver todo</button></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Producto</th><th>Variante</th><th class="text-end">Facturado</th><th class="text-end">Disponible</th><th style="width:150px">A devolver</th><th class="text-end">Precio</th><th class="text-end">Total</th></tr></thead><tbody id="itemsRows"><tr><td colspan="7" class="text-center text-muted py-5">Seleccioná una factura para cargar sus productos.</td></tr></tbody></table></div>
    </div>

    <div class="row justify-content-end"><div class="col-lg-4"><div class="card"><div class="card-body">
        <div class="d-flex justify-content-between mb-2"><span>Neto</span><strong id="netTotal">$ 0,00</strong></div>
        <div class="d-flex justify-content-between mb-3"><span>IVA</span><strong id="taxTotal">$ 0,00</strong></div><hr>
        <div class="d-flex justify-content-between align-items-center mb-3"><h5 class="mb-0">Total NC</h5><h3 id="grandTotal" class="mb-0">$ 0,00</h3></div>
        <button id="saveButton" class="btn btn-success w-100" disabled><i class="ri-save-line me-1"></i> Emitir Nota de Crédito</button>
    </div></div></div></div>
</form>

<script>
let sourceInvoice = null;
let idempotencyKey = crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random()}`;
const money = value => Number(value || 0).toLocaleString('es-AR', {style:'currency', currency:'ARS'});
const esc = value => { const el=document.createElement('div'); el.textContent=value??''; return el.innerHTML; };
const showAlert = (message, type='danger') => alertBox.innerHTML=`<div class="alert alert-${type}">${esc(message)}</div>`;

async function loadReasons(){
    const response=await fetch(`${window.APP_BASE_URL}/api/credit-note-reasons?per_page=100`,{headers:{Accept:'application/json'}});
    const data=await response.json();
    reason_id.innerHTML='<option value="">Seleccionar...</option>'+(data.data??data).filter(reason=>reason.is_active!==false).map(reason=>`<option value="${reason.id}">${esc(reason.name)}</option>`).join('');
}

async function loadInvoice(id){
    itemsRows.innerHTML='<tr><td colspan="7" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Cargando factura...</td></tr>';
    const response=await fetch(`${window.APP_BASE_URL}/api/credit-notes/source-invoices/${id}`,{headers:{Accept:'application/json'}});
    const data=await response.json().catch(()=>({}));
    if(!response.ok) throw new Error(data.message||'No se pudo cargar la factura.');
    sourceInvoice=data; invoiceSummary.classList.remove('d-none'); selectAllButton.disabled=false; saveButton.disabled=false;
    customerName.textContent=data.customer_name||data.client?.name||'—'; invoiceNumber.textContent=data.display_number||data.full_number||`Venta #${data.id}`;
    invoiceDate.textContent=formatDateTime(data.issue_date); originalTotal.textContent=money(data.total_amount); renderItems();
}

function lineValues(item, quantity){
    const gross=quantity*Number(item.unit_price_with_taxes||0); const total=gross*(1-Number(item.discount_percentage||0)/100);
    const rate=Number(item.tax_aliquot_percentage||0); const net=rate>0?total/(1+rate/100):total; return {net,tax:total-net,total};
}
function variantText(item){return [item.variant?.size?.name||item.size_name,item.variant?.color?.name||item.color_name].filter(Boolean).join(' · ')||'Sin variante';}
function renderItems(){
    const items=sourceInvoice?.items||[];
    itemsRows.innerHTML=items.map(item=>{const max=Number(item.remaining_credit_quantity||0);return `<tr class="${max<=0?'opacity-50':''}"><td><strong>${esc(item.product_name||item.description)}</strong><br><small class="text-muted">${esc(item.product_code||item.product_barcode||'')}</small></td><td>${esc(variantText(item))}</td><td class="text-end">${Number(item.quantity)}</td><td class="text-end"><strong>${max}</strong></td><td><input class="form-control credit-qty" data-item-id="${item.id}" type="number" min="0" max="${max}" step="0.01" value="0" ${max<=0?'disabled':''}></td><td class="text-end">${money(item.unit_price_with_taxes)}</td><td class="text-end fw-semibold" id="line-total-${item.id}">${money(0)}</td></tr>`}).join('')||'<tr><td colspan="7" class="text-center text-muted py-4">La factura no tiene productos acreditables.</td></tr>';
    document.querySelectorAll('.credit-qty').forEach(input=>input.addEventListener('input',recalculate)); recalculate();
}
function recalculate(){let net=0,tax=0,total=0;(sourceInvoice?.items||[]).forEach(item=>{const input=document.querySelector(`[data-item-id="${item.id}"]`);const qty=Math.min(Number(input?.value||0),Number(item.remaining_credit_quantity||0));const values=lineValues(item,qty);net+=values.net;tax+=values.tax;total+=values.total;document.getElementById(`line-total-${item.id}`)?.replaceChildren(document.createTextNode(money(values.total)));});netTotal.textContent=money(net);taxTotal.textContent=money(tax);grandTotal.textContent=money(total);}

document.addEventListener('DOMContentLoaded',async()=>{
    issue_date.value=new Date().toLocaleDateString('en-CA'); await loadReasons(); erpEnhanceRemoteSelect(source_invoice_id,{minimum:1});
    source_invoice_id.addEventListener('change',async()=>{if(!source_invoice_id.value)return;try{await loadInvoice(source_invoice_id.value)}catch(error){showAlert(error.message)}});
});
selectAllButton.addEventListener('click',()=>{document.querySelectorAll('.credit-qty').forEach(input=>{if(!input.disabled)input.value=input.max});recalculate();});
creditNoteForm.addEventListener('submit',async event=>{
    event.preventDefault();
    const items=[...document.querySelectorAll('.credit-qty')].map(input=>({original_invoice_item_id:Number(input.dataset.itemId),quantity:Number(input.value||0)})).filter(item=>item.quantity>0);
    if(!sourceInvoice)return showAlert('Seleccioná la factura original.','warning'); if(!reason_id.value)return showAlert('Seleccioná un motivo.','warning'); if(!items.length)return showAlert('Ingresá una cantidad para al menos un producto.','warning');
    saveButton.disabled=true;saveButton.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Emitiendo...';
    try{const response=await fetch(`${window.APP_BASE_URL}/api/credit-notes`,{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json'},body:JSON.stringify({related_invoice_id:Number(sourceInvoice.id),issue_date:issue_date.value,credit_note_reason_id:Number(reason_id.value),idempotency_key:idempotencyKey,items})});const data=await response.json().catch(()=>({}));if(!response.ok)throw new Error(data.errors?Object.values(data.errors).flat()[0]:data.message||'No se pudo emitir.');location.href=`${window.APP_BASE_URL}/demo/sales/credit-notes/${data.id}`;}catch(error){showAlert(error.message);saveButton.disabled=false;saveButton.innerHTML='<i class="ri-save-line me-1"></i> Emitir Nota de Crédito';}
});
</script>
@endsection
