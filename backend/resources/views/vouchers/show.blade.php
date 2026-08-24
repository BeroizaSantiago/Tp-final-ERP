@extends('layouts.app')
@section('content')
@php
$expired=$voucher->isExpired();
$status=$expired && $voucher->status==='available'?'expired':$voucher->status;
$labels=['available'=>'Disponible','reserved'=>'Reservado','used'=>'Usado','disabled'=>'Inhabilitado','expired'=>'Vencido'];
$colors=['available'=>'success','reserved'=>'warning','used'=>'secondary','disabled'=>'danger','expired'=>'danger'];
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <x-action-button action="back" :href="url('/demo/vouchers')" :icon-only="true" />
        <div><h4 class="mb-1">Validación de voucher</h4><small class="text-muted">{{ $voucher->code }}</small></div>
    </div>
    <div class="d-flex flex-column align-items-end gap-2">
        <div class="d-flex gap-2">
            <a class="btn btn-primary rounded-pill btn-icon" target="_blank" href="{{ url('/demo/vouchers/'.$voucher->id.'/print') }}" title="Imprimir voucher" aria-label="Imprimir voucher"><i class="icon-base ri ri-printer-line"></i></a>
            <a class="btn btn-success rounded-pill btn-icon" target="_blank" href="{{ url('/demo/vouchers/'.$voucher->id.'/download-image') }}" title="Descargar JPG" aria-label="Descargar JPG"><i class="icon-base ri ri-image-line"></i></a>
            @if($status==='available')<button class="btn btn-danger rounded-pill btn-icon" onclick="consumeVoucher()" title="Marcar como usado" aria-label="Marcar como usado"><i class="icon-base ri ri-checkbox-circle-line"></i></button>@endif
        </div>
    </div>
</div>
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-6"><small class="text-muted">Nombre</small>
                <h5>{{ $voucher->name }}</h5>
            </div>
            <div class="col-md-3"><small class="text-muted">Beneficio</small>
                <h4>@if($voucher->value_type==='percentage'){{ number_format($voucher->amount,2,',','.') }}%@else${{ number_format($voucher->amount,2,',','.') }}@endif</h4>@if($voucher->value_type==='percentage' && $voucher->maximum_discount_amount)<small class="text-muted">Tope: ${{ number_format($voucher->maximum_discount_amount,2,',','.') }}</small>@endif
            </div>
            <div class="col-md-3"><small class="text-muted">Válido hasta</small>
                <div class="fw-semibold">{{ $voucher->expires_at?->format('d/m/Y') ?? 'Sin vencimiento' }}</div>
            </div>
            <div class="col-12"><small class="text-muted">Mensaje</small>
                <div>{{ $voucher->message ?: '-' }}</div>
            </div>
            @if($voucher->used_at)<div class="col-md-4"><small class="text-muted">Utilizado el</small>
                <div>{{ $voucher->used_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="col-md-4"><small class="text-muted">Factura</small>
                <div>{{ $voucher->usedInvoice?->full_number ?? '-' }}</div>
            </div>@endif
        </div>
    </div>
</div>
    <div class="d-flex flex-column align-items-end gap-2">
        <span class="badge bg-{{ $colors[$status] }} fs-6">{{ $labels[$status] }}</span>
    </div>
<script>
    async function consumeVoucher() {
        if (!await window.erpConfirm('¿Confirmás que este voucher fue utilizado? Esta acción no se puede deshacer.')) return;
        const r = await fetch(`${window.APP_BASE_URL}/api/vouchers/{{ $voucher->id }}/consume`, {
            method: 'POST',
            headers: {
                Accept: 'application/json'
            }
        });
        const d = await r.json();
        if (!r.ok) {
            alert(d.message || 'No se pudo utilizar.');
            return
        }
        location.reload()
    }
</script>
@endsection
