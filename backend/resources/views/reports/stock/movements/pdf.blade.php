<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 18px;
            size: landscape
        }

        body {
            font-family: DejaVu Sans;
            font-size: 7px;
            color: #312d4b
        }

        h1 {
            color: #7048c8;
            margin: 0
        }

        .period {
            margin: 4px 0 12px
        }

        .summary {
            width: 100%;
            border-spacing: 8px
        }

        .summary td {
            background: #f4f0ff;
            border: 1px solid #ded3fb;
            padding: 8px;
            font-weight: bold
        }

        table.detail {
            width: 100%;
            border-collapse: collapse
        }

        th {
            background: #ede8f8;
            padding: 5px;
            text-align: left
        }

        td {
            padding: 5px;
            border-bottom: 1px solid #ddd
        }

        .right {
            text-align: right
        }

        .positive {
            color: #2e7d32
        }

        .negative {
            color: #c62828
        }
    </style>
</head>

<body>
    <h1>Movimientos de Stock</h1>
    <div class="period">{{ $report['filters']['date_from'] }} al {{ $report['filters']['date_to'] }}</div>
    <table class="summary">
        <tr>
            <td>Ingresos: {{ number_format($report['summary']['income'],2,',','.') }}</td>
            <td>Egresos: {{ number_format($report['summary']['expense'],2,',','.') }}</td>
            <td>Movimiento neto: {{ number_format($report['summary']['net'],2,',','.') }}</td>
        </tr>
    </table>
    <table class="detail">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo de Operación</th>
                <th>Motivo</th>
                <th>N.º Comprobante</th>
                <th>Código</th>
                <th>Cód. Barras</th>
                <th>Producto</th>
                <th class="right">Cantidad</th>
                <th class="right">Stock</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>@forelse($report['rows'] as $r)<tr>
                <td>{{ $r['date'] }}</td>
                <td>{{ $r['operation'] }}</td>
                <td>{{ $r['reason'] }}</td>
                <td>{{ $r['document'] }}</td>
                <td>{{ $r['product_code'] }}</td>
                <td>{{ $r['bar_code'] }}</td>
                <td>{{ $r['product'] }}</td>
                <td class="right {{ $r['quantity']>=0?'positive':'negative' }}">{{ $r['quantity']>0?'+':'' }}{{ number_format($r['quantity'],2,',','.') }}</td>
                <td class="right">{{ number_format($r['stock'],2,',','.') }}</td>
                <td>{{ $r['user'] }}</td>
            </tr>@empty<tr>
                <td colspan="10">Sin movimientos para los filtros seleccionados.</td>
            </tr>@endforelse</tbody>
    </table>
</body>

</html>