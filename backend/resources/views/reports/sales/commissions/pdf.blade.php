{{-- Vista: PDF del Listado de Comisiones. Presenta los cálculos del criterio seleccionado. --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 22px 24px;
            size: landscape;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #312d4b;
            font-size: 8px;
        }

        h1 {
            color: #7048c8;
            font-size: 18px;
            margin: 0 0 4px;
        }

        .meta {
            color: #6d6777;
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #ede8f8;
            color: #4b4561;
            padding: 5px 3px;
            text-transform: uppercase;
            font-size: 7px;
        }

        td {
            border-bottom: 1px solid #e3e0e8;
            padding: 5px 3px;
        }

        .right {
            text-align: right;
            white-space: nowrap;
        }

        .empty {
            text-align: center;
            color: #827d8b;
            padding: 14px;
        }

        tfoot td {
            background: #f2edff;
            color: #4b4561;
            font-weight: bold;
            border-top: 2px solid #8c57ff;
        }
    </style>
</head>

<body>
    @php($money = fn($value) => '$ ' . number_format((float)$value, 2, ',', '.'))
    <h1>Listado de Comisiones</h1>
    <div class="meta">
        Período: {{ $report['filters']['date_from'] }} al {{ $report['filters']['date_to'] }} ·
        Criterio: {{ $report['criterion_label'] }} ·
        Base de venta: {{ $report['filters']['gross_sale_commission'] ? 'Bruta' : 'Neta' }} ·
        Generado: {{ now()->format('d/m/Y H:i') }}
    </div>
    <table>
        <thead>
            <tr>
                <th>Vendedor</th>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Número</th>
                <th>Cliente</th>
                <th class="right">Venta</th>
                <th class="right">Cobrado</th>
                <th class="right">Com. cobro</th>
                <th class="right">Com. venta</th>
                <th class="right">Com. ganancia</th>
                <th class="right">Total comisión</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
            <tr>
                <td>{{ $row['seller'] }}</td>
                <td>{{ $row['issue_date'] }}</td>
                <td>{{ $row['receipt_type'] }}</td>
                <td>{{ $row['number'] }}</td>
                <td>{{ $row['client'] }}</td>
                <td class="right">{{ $money($row['sale_amount']) }}</td>
                <td class="right">{{ $money($row['collected_amount']) }}</td>
                <td class="right">{{ $money($row['collection_commission']) }}</td>
                <td class="right">{{ $money($row['sale_commission']) }}</td>
                <td class="right">{{ $money($row['profit_commission']) }}</td>
                <td class="right">{{ $money($row['total_commission']) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="empty">No se encontraron operaciones para los filtros seleccionados.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Totales · {{ $report['summary']['records'] }} registros</td>
                <td class="right">{{ $money($report['summary']['sale_amount']) }}</td>
                <td class="right">{{ $money($report['summary']['collected_amount']) }}</td>
                <td class="right">{{ $money($report['summary']['collection_commission']) }}</td>
                <td class="right">{{ $money($report['summary']['sale_commission']) }}</td>
                <td class="right">{{ $money($report['summary']['profit_commission']) }}</td>
                <td class="right">{{ $money($report['summary']['total_commission']) }}</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>