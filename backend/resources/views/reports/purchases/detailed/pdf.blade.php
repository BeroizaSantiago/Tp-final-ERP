{{-- Vista: PDF del Listado de Compras con Detalle. --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 20px;
            size: landscape
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #312d4b;
            font-size: 8px
        }

        h1 {
            color: #7048c8
        }

        .meta {
            color: #6d6777
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px
        }

        th {
            background: #ede8f8;
            padding: 6px;
            text-align: left
        }

        td {
            padding: 6px;
            border-bottom: 1px solid #ddd
        }

        .right {
            text-align: right
        }

        .total td {
            background: #f2edff;
            font-weight: bold
        }
    </style>
</head>

<body>@php($m=fn($v)=>'$ '.number_format($v,2,',','.'))<h1>Listado de Compras con Detalle</h1>
    <div class="meta">{{ $report['filters']['date_from'] }} al {{ $report['filters']['date_to'] }}</div>
    <table>
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Número</th>
                <th>Producto</th>
                <th>Proveedor</th>
                <th>Código</th>
                <th>Código de Barras</th>
                <th class="right">Cantidad</th>
                <th class="right">Precio Unitario</th>
                <th class="right">Descuento</th>
                <th class="right">IVA %</th>
                <th class="right">IVA</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr class="total">
                <td colspan="6">TOTAL</td>
                <td class="right">{{ number_format($report['summary']['quantity'],2,',','.') }}</td>
                <td></td>
                <td class="right">{{ $m($report['summary']['discount']) }}</td>
                <td></td>
                <td class="right">{{ $m($report['summary']['iva']) }}</td>
                <td class="right">{{ $m($report['summary']['total']) }}</td>
            </tr>@forelse($report['rows'] as $r)<tr>
                <td>{{ $r['type'] }}</td>
                <td>{{ $r['number'] }}</td>
                <td>{{ $r['product'] }}</td>
                <td>{{ $r['provider'] }}</td>
                <td>{{ $r['code'] }}</td>
                <td>{{ $r['barcode'] }}</td>
                <td class="right">{{ number_format($r['quantity'],2,',','.') }}</td>
                <td class="right">{{ $m($r['unit_price']) }}</td>
                <td class="right">{{ $m($r['discount']) }}</td>
                <td class="right">{{ number_format($r['tax_percentage'],2,',','.') }}%</td>
                <td class="right">{{ $m($r['iva']) }}</td>
                <td class="right">{{ $m($r['total']) }}</td>
            </tr>@empty<tr>
                <td colspan="12">Sin resultados.</td>
            </tr>@endforelse
        </tbody>
    </table>
</body>

</html>