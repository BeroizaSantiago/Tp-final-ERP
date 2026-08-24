<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 22px;
            size: landscape
        }

        body {
            font-family: DejaVu Sans;
            font-size: 9px;
            color: #312d4b
        }

        h1 {
            color: #7048c8;
            margin: 0
        }

        .period {
            margin: 4px 0 16px
        }

        .chart {
            width: 100%;
            height: 285px;
            margin: 5px 0 14px
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

<body>@php($m=fn($v)=>'$ '.number_format($v,2,',','.'))<h1>Resultado Financiero</h1>
    <div class="period">{{ $report['filters']['date_from'] }} al {{ $report['filters']['date_to'] }} · {{ $report['filters']['branch']??'Todas las sucursales' }}</div><img class="chart" src="{{ $report['chart'] }}">
    <table>
        <thead>
            <tr>
                <th>Período</th>
                <th class="right">Cobros</th>
                <th class="right">Pagos</th>
                <th class="right">Resultado</th>
            </tr>
        </thead>
        <tbody>
            <tr class="total">
                <td>TOTAL</td>
                <td class="right">{{ $m($report['summary']['collections']) }}</td>
                <td class="right">{{ $m($report['summary']['payments']) }}</td>
                <td class="right">{{ $m($report['summary']['result']) }}</td>
            </tr>@foreach($report['rows'] as $r)<tr>
                <td>{{ $r['period'] }}</td>
                <td class="right">{{ $m($r['collections']) }}</td>
                <td class="right">{{ $m($r['payments']) }}</td>
                <td class="right">{{ $m($r['result']) }}</td>
            </tr>@endforeach
        </tbody>
    </table>
</body>

</html>