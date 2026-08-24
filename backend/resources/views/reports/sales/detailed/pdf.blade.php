{{-- Vista: PDF del Listado con Detalle de Ventas. Una fila por producto vendido. --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 16px 18px;
            size: A3 landscape
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #312d4b;
            font-size: 6.5px
        }

        h1 {
            color: #7048c8;
            font-size: 17px;
            margin: 0 0 3px
        }

        .meta {
            color: #6d6777;
            margin-bottom: 8px
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        th {
            background: #ede8f8;
            padding: 4px 2px;
            font-size: 5.7px;
            text-transform: uppercase
        }

        td {
            padding: 4px 2px;
            border-bottom: 1px solid #e3e0e8
        }

        .right {
            text-align: right;
            white-space: nowrap
        }

        .totals td {
            background: #f2edff;
            font-weight: bold;
            border-top: 2px solid #8c57ff
        }

        .empty {
            text-align: center;
            padding: 12px
        }
    </style>
</head>

<body>@php($money=fn($v)=>'$ '.number_format((float)$v,2,',','.'))
    <h1>Listado con Detalle de Ventas</h1>
    <div class="meta">Período: {{ $report['filters']['date_from'] }} al {{ $report['filters']['date_to'] }} · {{ $report['filters']['with_variant']?'Con variantes':'Sin variantes' }} · {{ $report['summary']['records'] }} ítems</div>
    <table>
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Fecha</th>
                <th>Número</th>
                <th>Canal</th>
                <th>Sucursal</th>
                <th>Cliente</th>
                <th>Producto</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Categoría</th>
                <th>Lista</th>
                <th>Código</th>
                <th>Barra</th>
                <th>Referencia</th>@if($report['filters']['with_variant'])<th>Variante (talle/color/SKU)</th>@endif<th class="right">Cantidad</th>
                <th class="right">Costo</th>
                <th class="right">Precio</th>
                <th class="right">Descuento</th>
                <th class="right">Desc.%</th>
                <th class="right">IVA %</th>
                <th class="right">IVA</th>
                <th class="right">Ganancia</th>
                <th class="right">Total</th>
                <th>Pago</th>
                <th>Financiación</th>
            </tr>
        </thead>
        <tbody>
            <tr class="totals">
                <td colspan="{{ $report['filters']['with_variant']?15:14 }}">TOTALIZADORES</td>
                <td class="right">{{ number_format($report['summary']['quantity'],2,',','.') }}</td>
                <td class="right">{{ $money($report['summary']['cost']) }}</td>
                <td class="right" title="Importe total de venta">{{ $money($report['summary']['sale_amount']) }}</td>
                <td class="right">{{ $money($report['summary']['discount']) }}</td>
                <td colspan="2"></td>
                <td class="right">{{ $money($report['summary']['iva']) }}</td>
                <td class="right">{{ $money($report['summary']['profit']) }}</td>
                <td class="right">{{ $money($report['summary']['total']) }}</td>
                <td colspan="2"></td>
            </tr>
            @forelse($report['rows'] as $r)<tr>
                <td>{{ $r['receipt_type'] }}</td>
                <td>{{ $r['date'] }}</td>
                <td>{{ $r['number'] }}</td>
                <td>{{ $r['channel'] }}</td>
                <td>{{ $r['branch'] }}</td>
                <td>{{ $r['client'] }}</td>
                <td>{{ $r['product'] }}</td>
                <td>{{ $r['brand'] }}</td>
                <td>{{ $r['model'] }}</td>
                <td>{{ $r['category'] }}</td>
                <td>{{ $r['price_type'] }}</td>
                <td>{{ $r['internal_code'] }}</td>
                <td>{{ $r['barcode'] }}</td>
                <td>{{ $r['reference_code'] }}</td>@if($report['filters']['with_variant'])<td>{{ $r['variant'] }}</td>@endif<td class="right">{{ number_format($r['quantity'],2,',','.') }}</td>
                <td class="right">{{ $money($r['cost']) }}</td>
                <td class="right">{{ $money($r['unit_price']) }}</td>
                <td class="right">{{ $money($r['discount']) }}</td>
                <td class="right">{{ number_format($r['discount_percentage'],2,',','.') }}%</td>
                <td class="right">{{ number_format($r['tax_rate'],2,',','.') }}%</td>
                <td class="right">{{ $money($r['tax_amount']) }}</td>
                <td class="right">{{ $money($r['profit']) }}</td>
                <td class="right">{{ $money($r['total']) }}</td>
                <td>{{ $r['payment_method'] }}</td>
                <td>{{ $r['financing'] }}</td>
            </tr>@empty<tr>
                <td colspan="{{ $report['filters']['with_variant']?26:25 }}" class="empty">Sin ítems para los filtros seleccionados.</td>
            </tr>@endforelse
        </tbody>
    </table>
</body>

</html>