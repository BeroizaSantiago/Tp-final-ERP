<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 25px;
            size: landscape
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

<body>@php($m=fn($v)=>'$ '.number_format($v,2,',','.'))<h1>Ranking de Proveedores</h1>
    <div>{{ $report['filters']['date_from'] }} al {{ $report['filters']['date_to'] }}</div>
    <table>
        <thead>
            <tr>
                <th>Proveedor</th>
                <th class="right">Participación</th>
                <th class="right">Operaciones</th>
                <th class="right">Total Comprado</th>
                <th>Fecha Último Pago</th>
                <th class="right">Monto Último Pago</th>
            </tr>
        </thead>
        <tbody>
            <tr class="total">
                <td>TOTAL</td>
                <td></td>
                <td class="right">{{ $report['summary']['operations'] }}</td>
                <td class="right">{{ $m($report['summary']['total']) }}</td>
                <td colspan="2"></td>
            </tr>@forelse($report['rows'] as $r)<tr>
                <td>{{ $r['provider'] }}</td>
                <td class="right">{{ number_format($r['percentage'],2,',','.') }}%</td>
                <td class="right">{{ $r['operations'] }}</td>
                <td class="right">{{ $m($r['total']) }}</td>
                <td>{{ $r['last_payment_date'] }}</td>
                <td class="right">{{ $m($r['last_payment_amount']) }}</td>
            </tr>@empty<tr>
                <td colspan="6">Sin compras para el período seleccionado.</td>
            </tr>@endforelse
        </tbody>
    </table>
</body>

</html>