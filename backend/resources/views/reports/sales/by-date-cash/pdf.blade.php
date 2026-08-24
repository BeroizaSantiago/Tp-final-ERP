{{-- Vista: PDF de Ventas por Fechas y Cajas. Jerarquía de auditoría diaria. --}}
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
            font-size: 19px;
            margin: 0 0 4px
        }

        .meta {
            color: #6d6777;
            margin-bottom: 12px
        }

        .date {
            font-size: 13px;
            color: #7048c8;
            background: #f2edff;
            padding: 7px;
            margin-top: 14px
        }

        .box {
            font-size: 11px;
            color: #4b4561;
            margin: 10px 0 4px
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        th {
            background: #ede8f8;
            padding: 6px;
            text-align: left;
            font-size: 8px;
            text-transform: uppercase
        }

        td {
            padding: 6px;
            border-bottom: 1px solid #e3e0e8
        }

        .right {
            text-align: right
        }

        .subtotal td {
            font-weight: bold;
            background: #f8f6fc;
            border-top: 1px solid #8c57ff
        }

        .daily td {
            font-weight: bold;
            color: #7048c8;
            background: #f2edff
        }

        .general {
            margin-top: 18px;
            background: #7048c8;
            color: white;
            padding: 10px;
            font-size: 13px
        }

        .general span {
            float: right
        }

        .empty {
            text-align: center;
            padding: 15px;
            color: #827d8b
        }
    </style>
</head>

<body>@php($money=fn($v)=>'$ '.number_format((float)$v,2,',','.'))
    <h1>Reporte de Ventas por Fechas y Cajas</h1>
    <div class="meta">Período: {{ $report['filters']['date_from'] }} al {{ $report['filters']['date_to'] }} · Generado: {{ now()->format('d/m/Y H:i') }}</div>
    @forelse($report['dates'] as $day)<div class="date">Fecha: {{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }}</div>@foreach($day['boxes'] as $box)<div class="box">Caja: {{ $box['cash_box'] }}</div>
    <table>
        <thead>
            <tr>
                <th>Cajero</th>
                <th>Método de Pago</th>
                <th class="right">Operaciones</th>
                <th class="right">Importe Total</th>
            </tr>
        </thead>
        <tbody>@foreach($box['cashiers'] as $cashier)@foreach($cashier['methods'] as $index=>$method)<tr>
                <td>{{ $index===0?$cashier['cashier']:'' }}</td>
                <td>{{ $method['payment_method'] }}</td>
                <td class="right">{{ $method['operations'] }}</td>
                <td class="right">{{ $money($method['total']) }}</td>
            </tr>@endforeach @endforeach<tr class="subtotal">
                <td colspan="2">Subtotal {{ $box['cash_box'] }}</td>
                <td class="right">{{ $box['operations'] }}</td>
                <td class="right">{{ $money($box['total']) }}</td>
            </tr>
        </tbody>
    </table>@endforeach<table>
        <tr class="daily">
            <td>Total Diario</td>
            <td class="right">{{ $day['operations'] }} operaciones</td>
            <td class="right">{{ $money($day['total']) }}</td>
        </tr>
    </table>@empty<div class="empty">Sin cobros de ventas para los filtros seleccionados.</div>@endforelse
    <div class="general">TOTAL GENERAL · {{ $report['summary']['operations'] }} operaciones <span>{{ $money($report['summary']['total']) }}</span></div>
</body>

</html>