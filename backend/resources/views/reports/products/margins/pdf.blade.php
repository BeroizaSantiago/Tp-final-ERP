{{-- Vista: PDF compartido de márgenes de productos. --}}
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
            padding: 7px;
            text-align: left
        }

        td {
            padding: 7px;
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

<body>@php($money=fn($v)=>'$ '.number_format((float)$v,2,',','.'))<h1>{{ $report['title'] }}</h1>
    <div class="meta">Período: {{ $report['filters']['date_from'] }} al {{ $report['filters']['date_to'] }} · Sucursal: {{ $report['filters']['branch']??'Todas' }}</div>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>{{ match($report['grouping']) { 'category' => 'Categoría', 'brand' => 'Marca', default => 'Producto' } }}</th>
                <th class="right">Total Venta</th>
                <th class="right">Ganancia</th>
                <th class="right">Margen Promedio</th>
                <th class="right">Rentabilidad Promedio</th>
                <th class="right">Porcentaje</th>
            </tr>
        </thead>
        <tbody>
            <tr class="total">
                <td colspan="2">TOTAL</td>
                <td class="right">{{ $money($report['summary']['total_sale']) }}</td>
                <td class="right">{{ $money($report['summary']['profit']) }}</td>
                <td colspan="3"></td>
            </tr>@forelse($report['rows'] as $r)
            <tr>
                <td>{{ $r['code'] }}</td>
                <td>{{ $r['product'] }}</td>
                <td class="right">{{ $money($r['total_sale']) }}</td>
                <td class="right">{{ $money($r['profit']) }}</td>
                <td class="right">{{ number_format($r['average_margin'],2,',','.') }}%</td>
                <td class="right">{{ number_format($r['average_profitability'],2,',','.') }}%</td>
                <td class="right">{{ number_format($r['percentage'],2,',','.') }}%</td>
            </tr>@empty<tr>
                <td colspan="7" class="empty">Sin resultados.</td>
            </tr>@endforelse
        </tbody>
    </table>
</body>

</html>
