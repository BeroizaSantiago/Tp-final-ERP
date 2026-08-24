{{-- Vista: Detalle de Facturas de Venta. Muestra la información completa de un registro de Facturas de Venta. --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <h3>Factura {{ $invoice->full_number ?? $invoice->id }}</h3>

    <p><strong>Cliente:</strong> {{ $invoice->customer_name }}</p>
    <p><strong>Fecha:</strong> {{ optional($invoice->issue_date)->format('d/m/Y H:i') ?? '-' }}</p>
    <p><strong>Total:</strong> $ {{ number_format($invoice->total_amount, 2, ',', '.') }}</p>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cant.</th>
                <th>Precio c/IVA</th>
                <th>IVA</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>$ {{ number_format($item->unit_price_with_taxes, 2, ',', '.') }}</td>
                    <td>{{ $item->tax_aliquot_percentage }}%</td>
                    <td>$ {{ number_format($item->total_amount, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
