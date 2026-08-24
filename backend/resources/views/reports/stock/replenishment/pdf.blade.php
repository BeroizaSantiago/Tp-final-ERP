<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 15px;
            size: landscape
        }

        body {
            font-family: DejaVu Sans;
            font-size: 6.5px;
            color: #312d4b
        }

        h1 {
            color: #7048c8;
            margin: 0
        }

        .period {
            margin: 4px 0 10px
        }

        .summary {
            background: #f4f0ff;
            border: 1px solid #ded3fb;
            padding: 8px;
            margin-bottom: 8px
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        th {
            background: #ede8f8;
            padding: 4px;
            text-align: left
        }

        td {
            padding: 4px;
            border-bottom: 1px solid #ddd
        }

        .right {
            text-align: right
        }

        .suggested {
            font-weight: bold;
            color: #c62828
        }
    </style>
</head>

<body>
    <h1>Reposición de Stock</h1>
    <div class="period">Sucursal: {{ $report['filters']['branch'] }} · Criterio: {{ $report['criterion_label'] }}</div>
    <div class="summary">Registros: {{ $report['summary']['products'] }} · Stock actual: {{ number_format($report['summary']['current'],2,',','.') }} · Cantidad sugerida: {{ number_format($report['summary']['suggested'],2,',','.') }}</div>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Cód. Barras</th>
                <th>Producto</th>
                <th>Talle</th>
                <th>Color</th>
                <th class="right">{{ $report['criterion_label'] }}</th>
                <th class="right">Stock Actual</th>
                <th>Sucursal</th>
                <th>Depósito</th>
                <th>Proveedor</th>
                <th>Categoría</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th class="right">Sugerido</th>
            </tr>
        </thead>
        <tbody>@forelse($report['rows'] as $r)<tr>
                <td>{{ $r['code'] }}</td>
                <td>{{ $r['bar_code'] }}</td>
                <td>{{ $r['product'] }}</td>
                <td>{{ $r['size'] }}</td>
                <td>{{ $r['color'] }}</td>
                <td class="right">{{ number_format($r['target'],2,',','.') }}</td>
                <td class="right">{{ number_format($r['current'],2,',','.') }}</td>
                <td>{{ $r['branch'] }}</td>
                <td>{{ $r['warehouse'] }}</td>
                <td>{{ $r['provider'] }}</td>
                <td>{{ $r['category'] }}</td>
                <td>{{ $r['brand'] }}</td>
                <td>{{ $r['model'] }}</td>
                <td class="right suggested">{{ number_format($r['suggested'],2,',','.') }}</td>
            </tr>@empty<tr>
                <td colspan="14">No hay productos que requieran reposición.</td>
            </tr>@endforelse</tbody>
    </table>
</body>

</html>