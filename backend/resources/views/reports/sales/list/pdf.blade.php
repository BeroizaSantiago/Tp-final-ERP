{{-- Vista: PDF del Listado de Ventas. Resumen y detalle cronológico de comprobantes. --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 18px 20px;
            size: A3 landscape
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #312d4b;
            font-size: 7px
        }

        h1 {
            color: #7048c8;
            font-size: 18px;
            margin: 0 0 3px
        }

        .meta {
            color: #6d6777;
            margin-bottom: 9px
        }

        .summary {
            width: 100%;
            border-spacing: 5px;
            margin-left: -5px
        }

        .summary td {
            background: #f2edff;
            border: 1px solid #ddd2ff;
            padding: 6px;
            width: 25%
        }

        .label {
            color: #6d6777;
            text-transform: uppercase
        }

        .value {
            font-size: 12px;
            font-weight: bold;
            margin-top: 2px
        }

        table.data {
            width: 100%;
            border-collapse: collapse
        }

        th {
            background: #ede8f8;
            padding: 4px 2px;
            text-transform: uppercase;
            font-size: 6px
        }

        td {
            border-bottom: 1px solid #e3e0e8;
            padding: 4px 2px
        }

        .right {
            text-align: right;
            white-space: nowrap
        }

        .totalizers td {
            background: #f2edff;
            font-weight: bold;
            border-top: 2px solid #8c57ff
        }

        .empty {
            text-align: center;
            padding: 12px;
            color: #827d8b
        }
    </style>
</head>

<body>@php($money=fn($v)=>'$ '.number_format((float)$v,2,',','.'))
    <h1>Listado de Ventas</h1>
    <div class="meta">Período: {{ $report['filters']['date_from'] }} al {{ $report['filters']['date_to'] }} · Generado: {{ now()->format('d/m/Y H:i') }}</div>
    <table class="summary">
        <tr>
            <td>
                <div class="label">Total General facturado</div>
                <div class="value">{{ $money($report['summary']['total']) }}</div>
            </td>
            <td>
                <div class="label">Ticket Promedio</div>
                <div class="value">{{ $money($report['summary']['average_ticket']) }}</div>
            </td>
            <td>
                <div class="label">Unidades vendidas</div>
                <div class="value">{{ number_format($report['summary']['units'],2,',','.') }}</div>
            </td>
            <td>
                <div class="label">Operaciones</div>
                <div class="value">{{ $report['summary']['operations'] }}</div>
            </td>
        </tr>
    </table>
    <table class="data">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Número</th>
                <th>Canal</th>
                <th>Sucursal</th>
                <th>Asociado</th>
                <th>Cliente</th>
                <th>Doc. cliente</th>
                <th>Provincia</th>
                <th>Vendedor</th>
                <th>Lista</th>
                <th class="right">Neto</th>
                <th class="right">No grav.</th>
                <th class="right">Exento</th>
                <th class="right">Recargos</th>
                <th class="right">Descuentos</th>
                <th class="right">Desc. %</th>
                <th class="right">Redondeo</th>
                <th class="right">Envío</th>
                <th class="right">IVA</th>
                <th class="right">Ganancia</th>
                <th class="right">Unid.</th>
                <th class="right">Total</th>
                <th>Pago</th>
                <th>Financiación</th>
            </tr>
        </thead>
        <tbody>
            <tr class="totalizers">
                <td colspan="11">TOTALIZADORES</td>
                <td class="right">{{ $money($report['summary']['net']) }}</td>
                <td colspan="7"></td>
                <td class="right">{{ $money($report['summary']['iva']) }}</td>
                <td class="right">{{ $money($report['summary']['profit']) }}</td>
                <td class="right">{{ number_format($report['summary']['units'],2,',','.') }}</td>
                <td class="right">{{ $money($report['summary']['total']) }}</td>
                <td colspan="2"></td>
            </tr>
            @forelse($report['rows'] as $r)<tr>
                <td>{{ $r['date'] }}</td>
                <td>{{ $r['receipt_type'] }}</td>
                <td>{{ $r['number'] }}</td>
                <td>{{ $r['channel'] }}</td>
                <td>{{ $r['branch'] }}</td>
                <td>{{ $r['related_document'] }}</td>
                <td>{{ $r['client'] }}</td>
                <td>{{ $r['client_document'] }}</td>
                <td>{{ $r['province'] }}</td>
                <td>{{ $r['seller'] }}</td>
                <td>{{ $r['price_type'] }}</td>
                <td class="right">{{ $money($r['net']) }}</td>
                <td class="right">{{ $money($r['non_taxed']) }}</td>
                <td class="right">{{ $money($r['exempt']) }}</td>
                <td class="right">{{ $money($r['surcharges']) }}</td>
                <td class="right">{{ $money($r['discounts']) }}</td>
                <td class="right">{{ number_format($r['discount_percentage'],2,',','.') }}%</td>
                <td class="right">{{ $money($r['rounding']) }}</td>
                <td class="right">{{ $money($r['shipping_cost']) }}</td>
                <td class="right">{{ $money($r['iva']) }}</td>
                <td class="right">{{ $money($r['profit']) }}</td>
                <td class="right">{{ number_format($r['units'],2,',','.') }}</td>
                <td class="right">{{ $money($r['total']) }}</td>
                <td>{{ $r['payment_method'] }}</td>
                <td>{{ $r['financing'] }}</td>
            </tr>@empty<tr>
                <td colspan="25" class="empty">Sin comprobantes para los filtros seleccionados.</td>
            </tr>@endforelse
        </tbody>
    </table>
</body>

</html>