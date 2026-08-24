<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 25px
        }

        body {
            font-family: DejaVu Sans;
            font-size: 9px;
            color: #312d4b
        }

        h1 {
            color: #7048c8
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

<body>@php($m=fn($v)=>'$ '.number_format($v,2,',','.'))<h1>Ranking de Clientes</h1>
    <div>{{ $report['filters']['date_from'] }} al {{ $report['filters']['date_to'] }}</div>
    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th class="right">Total de Ventas</th>
                <th class="right">Cantidad de Operaciones</th>
                <th class="right">Ganancia</th>
            </tr>
        </thead>
        <tbody>
            <tr class="total">
                <td>TOTAL</td>
                <td class="right">{{ $m($report['summary']['total_sales']) }}</td>
                <td class="right">{{ $report['summary']['operations'] }}</td>
                <td class="right">{{ $m($report['summary']['profit']) }}</td>
            </tr>@foreach($report['rows'] as $r)<tr>
                <td>{{ $r['client'] }}</td>
                <td class="right">{{ $m($r['total_sales']) }}</td>
                <td class="right">{{ $r['operations'] }}</td>
                <td class="right">{{ $m($r['profit']) }}</td>
            </tr>@endforeach
        </tbody>
    </table>
</body>

</html>