{{-- Vista: PDF del Reporte de Ventas Consolidado. Presenta todas las agrupaciones del período en formato imprimible. --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 24px 28px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #312d4b;
            font-size: 10px;
        }

        h1 {
            color: #7048c8;
            font-size: 20px;
            margin: 0 0 4px;
        }

        h2 {
            color: #4b4561;
            font-size: 13px;
            margin: 18px 0 6px;
            border-bottom: 2px solid #8c57ff;
            padding-bottom: 4px;
        }

        .meta {
            color: #6d6777;
            margin-bottom: 14px;
        }

        .summary {
            width: 100%;
            border-spacing: 6px;
            margin-left: -6px;
        }

        .summary td {
            background: #f2edff;
            border: 1px solid #ddd2ff;
            border-radius: 4px;
            padding: 8px;
            width: 25%;
        }

        .summary .label {
            color: #6d6777;
            font-size: 8px;
            text-transform: uppercase;
        }

        .summary .value {
            color: #3b3154;
            font-size: 14px;
            font-weight: bold;
            margin-top: 3px;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
        }

        table.data tr {
            page-break-inside: avoid;
        }

        table.data th {
            background: #ede8f8;
            color: #4b4561;
            text-transform: uppercase;
            font-size: 8px;
            padding: 6px;
            text-align: left;
        }

        table.data td {
            border-bottom: 1px solid #e3e0e8;
            padding: 6px;
        }

        .right {
            text-align: right !important;
        }

        .empty {
            color: #827d8b;
            text-align: center;
            padding: 10px;
        }

        .total {
            color: #7048c8;
        }
    </style>
</head>

<body>
    @php
    $money = fn($value) => '$ ' . number_format((float)$value, 2, ',', '.');
    $number = fn($value) => number_format((float)$value, 2, ',', '.');
    @endphp
    <h1>Reporte de Ventas Consolidado</h1>
    <div class="meta">
        Período: {{ $report['filters']['date_from'] }} al {{ $report['filters']['date_to'] }}
        · Sucursal: {{ $report['filters']['branch'] ?: 'Todas' }}
        · Generado: {{ now()->format('d/m/Y H:i') }}
    </div>

    <h2>Resumen de Ventas</h2>
    <table class="summary">
        <tr>
            <td>
                <div class="label">Operaciones</div>
                <div class="value">{{ $report['summary']['operations'] }}</div>
            </td>
            <td>
                <div class="label">Unidades vendidas</div>
                <div class="value">{{ $number($report['summary']['units']) }}</div>
            </td>
            <td>
                <div class="label">Ventas totales</div>
                <div class="value">{{ $money($report['summary']['sales_total']) }}</div>
            </td>
            <td>
                <div class="label">Ticket promedio</div>
                <div class="value">{{ $money($report['summary']['average_ticket']) }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Notas de crédito (-)</div>
                <div class="value">{{ $money($report['summary']['credit_notes_total']) }}</div>
            </td>
            <td>
                <div class="label">Notas de débito (+)</div>
                <div class="value">{{ $money($report['summary']['debit_notes_total']) }}</div>
            </td>
            <td colspan="2">
                <div class="label">Total general</div>
                <div class="value total">{{ $money($report['summary']['grand_total']) }}</div>
            </td>
        </tr>
    </table>

    <h2>Ventas por Sucursal</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Sucursal</th>
                <th class="right">Operaciones</th>
                <th class="right">Unidades</th>
                <th class="right">Ventas</th>
                <th class="right">Promedio</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['by_branch'] as $row)<tr>
                <td>{{ $row['name'] }}</td>
                <td class="right">{{ $row['operations'] }}</td>
                <td class="right">{{ $number($row['units']) }}</td>
                <td class="right">{{ $money($row['total']) }}</td>
                <td class="right">{{ $money($row['average']) }}</td>
            </tr>@empty<tr>
                <td colspan="5" class="empty">Sin ventas para los filtros seleccionados.</td>
            </tr>@endforelse
        </tbody>
    </table>

    <h2>Ventas por Categoría</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Categoría</th>
                <th class="right">Unidades</th>
                <th class="right">Importe</th>
                <th class="right">Participación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['by_category'] as $row)<tr>
                <td>{{ $row['name'] }}</td>
                <td class="right">{{ $number($row['units']) }}</td>
                <td class="right">{{ $money($row['total']) }}</td>
                <td class="right">{{ $number($row['percentage']) }}%</td>
            </tr>@empty<tr>
                <td colspan="4" class="empty">Sin datos.</td>
            </tr>@endforelse
        </tbody>
    </table>

    <h2>Ventas por Marca</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Marca</th>
                <th class="right">Unidades</th>
                <th class="right">Importe</th>
                <th class="right">Participación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['by_brand'] as $row)<tr>
                <td>{{ $row['name'] }}</td>
                <td class="right">{{ $number($row['units']) }}</td>
                <td class="right">{{ $money($row['total']) }}</td>
                <td class="right">{{ $number($row['percentage']) }}%</td>
            </tr>@empty<tr>
                <td colspan="4" class="empty">Sin datos.</td>
            </tr>@endforelse
        </tbody>
    </table>

    <h2>Ventas por Medio de Pago</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Medio</th>
                <th class="right">Operaciones</th>
                <th class="right">Monto cobrado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['by_payment_method'] as $row)<tr>
                <td>{{ $row['name'] }}</td>
                <td class="right">{{ $row['operations'] }}</td>
                <td class="right">{{ $money($row['total']) }}</td>
            </tr>@empty<tr>
                <td colspan="3" class="empty">Sin pagos registrados.</td>
            </tr>@endforelse
        </tbody>
    </table>

    <h2>Ventas por Canal</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Canal</th>
                <th class="right">Operaciones</th>
                <th class="right">Ventas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['by_channel'] as $row)<tr>
                <td>{{ $row['name'] }}</td>
                <td class="right">{{ $row['operations'] }}</td>
                <td class="right">{{ $money($row['total']) }}</td>
            </tr>@empty<tr>
                <td colspan="3" class="empty">Sin datos.</td>
            </tr>@endforelse
        </tbody>
    </table>
</body>

</html>