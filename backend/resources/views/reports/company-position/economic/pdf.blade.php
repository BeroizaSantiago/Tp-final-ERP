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
            font-size: 8px;
            color: #312d4b
        }

        h1 {
            color: #7048c8;
            margin: 0
        }

        .period {
            margin: 4px 0 14px
        }

        .cards {
            width: 100%;
            border-spacing: 8px;
            margin-bottom: 10px
        }

        .card {
            background: #f4f0ff;
            border: 1px solid #ded3fb;
            border-radius: 6px;
            padding: 10px
        }

        .label {
            color: #6f6b7d
        }

        .value {
            font-size: 15px;
            font-weight: bold;
            margin-top: 4px
        }

        .chart {
            width: 100%;
            height: 275px;
            margin: 5px 0 12px
        }

        table.detail {
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

<body>
    @php($m=fn($v)=>'$ '.number_format($v,2,',','.'))
    <h1>Resultado Económico</h1>
    <div class="period">{{ $report['filters']['date_from'] }} al {{ $report['filters']['date_to'] }} · {{ $report['filters']['branch']??'Todas las sucursales' }}</div>
    <table class="cards">
        <tr>
            <td class="card">
                <div class="label">Ventas Netas</div>
                <div class="value">{{ $m($report['summary']['sales']) }}</div>
            </td>
            <td class="card">
                <div class="label">Gastos / Compras Netos</div>
                <div class="value">{{ $m($report['summary']['expenses_and_purchases']) }}</div>
            </td>
            <td class="card">
                <div class="label">Ganancia Neta</div>
                <div class="value">{{ $m($report['summary']['net_profit']) }}</div>
            </td>
            <td class="card">
                <div class="label">Rentabilidad</div>
                <div class="value">{{ number_format($report['summary']['profitability'],2,',','.') }}%</div>
            </td>
        </tr>
    </table>
    <img class="chart" src="{{ $report['chart'] }}">
    <table class="detail">
        <thead>
            <tr>
                <th>Período</th>
                <th class="right">Ventas Netas</th>
                <th class="right">Gastos Netos</th>
                <th class="right">Compras Netas</th>
                <th class="right">Resultado</th>
                <th class="right">Rentabilidad</th>
            </tr>
        </thead>
        <tbody>
            <tr class="total">
                <td>TOTAL</td>
                <td class="right">{{ $m($report['summary']['sales']) }}</td>
                <td class="right">{{ $m($report['summary']['expenses']) }}</td>
                <td class="right">{{ $m($report['summary']['purchases']) }}</td>
                <td class="right">{{ $m($report['summary']['net_profit']) }}</td>
                <td class="right">{{ number_format($report['summary']['profitability'],2,',','.') }}%</td>
            </tr>@foreach($report['rows'] as $r)<tr>
                <td>{{ $r['period'] }}</td>
                <td class="right">{{ $m($r['sales']) }}</td>
                <td class="right">{{ $m($r['expenses']) }}</td>
                <td class="right">{{ $m($r['purchases']) }}</td>
                <td class="right">{{ $m($r['result']) }}</td>
                <td class="right">{{ number_format($r['profitability'],2,',','.') }}%</td>
            </tr>@endforeach
        </tbody>
    </table>
</body>

</html>