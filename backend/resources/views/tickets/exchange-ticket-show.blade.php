{{-- Vista: Detalle del ticket de cambio. Muestra los datos y productos asociados al ticket. --}}
@extends('layouts.app')

@section('content')

@php
    $isExpired = $ticket->expiration_date
        ? now()->startOfDay()->greaterThan($ticket->expiration_date->startOfDay())
        : false;

    $isUsed = (bool) $ticket->used;

    if ($isUsed) {
        $statusText = 'Utilizado';
        $statusClass = 'danger';
    } elseif ($isExpired) {
        $statusText = 'Vencido';
        $statusClass = 'warning';
    } else {
        $statusText = 'Disponible';
        $statusClass = 'success';
    }
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Validación de Ticket de Cambio</h4>
        <small class="text-muted">{{ $ticket->ticket_number }}</small>
    </div>

    <span class="badge bg-{{ $statusClass }} fs-6">
        {{ $statusText }}
    </span>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="row">

            <div class="col-md-3 mb-3">
                <small class="text-muted">Número de ticket</small><br>
                <strong>{{ $ticket->ticket_number }}</strong>
            </div>

            <div class="col-md-3 mb-3">
                <small class="text-muted">Factura</small><br>
                <strong>
                    {{ $ticket->invoice->full_number ?? $ticket->invoice->id }}
                </strong>
            </div>

            <div class="col-md-3 mb-3">
                <small class="text-muted">Fecha de venta</small><br>
                <strong>
                    {{ optional($ticket->invoice->issue_date)->format('d/m/Y') ?? '-' }}
                </strong>
            </div>

            <div class="col-md-3 mb-3">
                <small class="text-muted">Válido hasta</small><br>
                <strong>
                    {{ optional($ticket->expiration_date)->format('d/m/Y') ?? '-' }}
                </strong>
            </div>

            <div class="col-md-6 mb-3">
                <small class="text-muted">Cliente</small><br>
                <strong>
                    {{ $ticket->invoice->customer_name ?? 'Consumidor Final' }}
                </strong>
            </div>

            <div class="col-md-3 mb-3">
                <small class="text-muted">Estado</small><br>
                <span class="badge bg-{{ $statusClass }}">
                    {{ $statusText }}
                </span>
            </div>

            <div class="col-md-3 mb-3">
                <small class="text-muted">Usado el</small><br>
                <strong>
                    {{ optional($ticket->used_at)->format('d/m/Y H:i') ?? '-' }}
                </strong>
            </div>

        </div>
    </div>
</div>

@if(!$isUsed && !$isExpired)
    <div class="card mb-4">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="mb-1">Registrar utilización</h5>
                <small class="text-muted">
                    Al confirmar, este ticket no podrá volver a utilizarse.
                </small>
            </div>

            <button
                type="button"
                class="btn btn-danger"
                onclick="useExchangeTicket()"
            >
                Usar ticket
            </button>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Productos habilitados para cambio</h5>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Talle</th>
                    <th>Color</th>
                    <th class="text-end">Cantidad</th>
                </tr>
            </thead>

            <tbody>
                @forelse($ticket->items as $item)
                    <tr>
                        <td>
                            {{ $item->invoiceItem->product_code ?? '-' }}
                        </td>

                        <td>
                            {{ $item->invoiceItem->product_name
                                ?? $item->invoiceItem->description
                                ?? 'Producto' }}
                        </td>

                        <td>
                            {{ $item->invoiceItem->size_name ?? '-' }}
                        </td>

                        <td>
                            {{ $item->invoiceItem->color_name ?? '-' }}
                        </td>

                        <td class="text-end">
                            {{ $item->quantity }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            El ticket no tiene productos asociados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


<script>
async function useExchangeTicket() {
    const confirmed = await window.erpConfirm(
        '¿Confirmás que este ticket será utilizado para realizar el cambio?\n\n' +
        'Esta acción no se puede deshacer.'
    );

    if (!confirmed) {
        return;
    }

    const response = await fetch(`${window.APP_BASE_URL}/demo/exchange-tickets/{{ $ticket->id }}/use`,
        {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                used_invoice_id: null
            })
        }
    );

    const data = await response.json();

    if (!response.ok) {
        alert(data.message ?? 'No se pudo utilizar el ticket.');
        return;
    }

    alert(data.message ?? 'Ticket utilizado correctamente.');
    window.location.reload();
}
</script>

@endsection
