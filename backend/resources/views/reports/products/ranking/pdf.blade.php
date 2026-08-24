{{-- Vista: PDF del Ranking de Productos Vendidos. --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 24px 28px
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #312d4b;
            font-size: 9px
        }

        h1 {
            color: #7048c8;
            font-size: 19px
        }

        .meta {
            color: #6d6777;
            margin-bottom: 12px
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        th {
            background: #ede8f8;
            padding: 6px;
            text-align: left
        }

        td {
            padding: 6px;
            border-bottom: 1px solid #e3e0e8
        }

        .right {
            text-align: right
        }

        .total td {
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

<body>@php($money=fn($v)=>'$ '.number_format((float)$v,2,',','.'))<h1>Ranking de Productos Vendidos</h1>
    <div class="meta">Período: {{ $report['filters']['date_from'] }} al {{ $report['filters']['date_to'] }} · Top {{ $report['filters']['top'] }} · Orden: {{ $report['filters']['order_by'] }}</div>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Categoría</th>
                <th>Proveedor</th>
                <th class="right">Total Venta</th>
                <th class="right">Unidades</th>
                <th class="right">Operaciones</th>
                <th class="right">Ganancia</th>
            </tr>
        </thead>
        <tbody>
            <tr class="total">
                <td colspan="6">TOTAL RANKING</td>
                <td class="right">{{ $money($report['summary']['total_sale']) }}</td>
                <td class="right">{{ number_format($report['summary']['units'],2,',','.') }}</td>
                <td></td>
                <td class="right">{{ $money($report['summary']['profit']) }}</td>
            </tr>@forelse($report['rows'] as $r)<tr>
                <td>{{ $r['code'] }}</td>
                <td>{{ $r['product'] }}</td>
                <td>{{ $r['brand'] }}</td>
                <td>{{ $r['model'] }}</td>
                <td>{{ $r['category'] }}</td>
                <td>{{ $r['provider'] }}</td>
                <td class="right">{{ $money($r['total_sale']) }}</td>
                <td class="right">{{ number_format($r['units'],2,',','.') }}</td>
                <td class="right">{{ $r['operations'] }}</td>
                <td class="right">{{ $money($r['profit']) }}</td>
            </tr>@empty<tr>
                <td colspan="10" class="empty">Sin resultados.</td>
            </tr>@endforelse
        </tbody>
    </table>
</body>

</html>