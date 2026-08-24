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
            font-family: DejaVu Sans;
            font-size: 8px;
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

<body>@php($m=fn($v)=>'$ '.number_format($v,2,',','.'))<h1>Listado de Acreedores</h1>
    <table>
        <thead>
            <tr>
                <th>Proveedor</th>
                <th>Moneda</th>
                <th class="right">Saldo</th>
                <th class="right">Deuda Vencida</th>
                <th class="right">Deuda Futura</th>
                <th>Fecha Último Pago</th>
                <th class="right">Monto Último Pago</th>
                <th>E-mail</th>
                <th>Teléfono</th>
            </tr>
        </thead>
        <tbody>
            <tr class="total">
                <td colspan="2">TOTAL</td>
                <td class="right">{{ $m($report['summary']['balance']) }}</td>
                <td class="right">{{ $m($report['summary']['expired']) }}</td>
                <td class="right">{{ $m($report['summary']['future']) }}</td>
                <td colspan="4"></td>
            </tr>@forelse($report['rows'] as $r)<tr>
                <td>{{ $r['provider'] }}</td>
                <td>{{ $r['currency'] }}</td>
                <td class="right">{{ $m($r['balance']) }}</td>
                <td class="right">{{ $m($r['expired']) }}</td>
                <td class="right">{{ $m($r['future']) }}</td>
                <td>{{ $r['last_payment_date'] }}</td>
                <td class="right">{{ $m($r['last_payment_amount']) }}</td>
                <td>{{ $r['email'] }}</td>
                <td>{{ $r['phone'] }}</td>
            </tr>@empty<tr>
                <td colspan="9">Sin acreedores para los filtros seleccionados.</td>
            </tr>@endforelse
        </tbody>
    </table>
</body>

</html>