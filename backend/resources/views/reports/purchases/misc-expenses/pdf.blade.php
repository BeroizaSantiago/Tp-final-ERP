{{-- Vista: PDF del Listado de Gastos Varios. --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 25px
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #312d4b;
            font-size: 9px
        }

        h1 {
            color: #7048c8
        }

        .meta {
            color: #6d6777
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px
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

<body>@php($m=fn($v)=>'$ '.number_format($v,2,',','.'))<h1>Listado de Gastos Varios</h1>
    <div class="meta">{{ $report['filters']['date_from'] }} al {{ $report['filters']['date_to'] }}</div>
    <table>
        <thead>
            <tr>
                <th>Categoría</th>
                <th>Subcategoría</th>
                <th class="right">Neto</th>
                <th class="right">No Gravado</th>
                <th class="right">Exento</th>
                <th class="right">Descuento</th>
                <th class="right">IVA</th>
                <th class="right">Total</th>
                <th class="right">Porcentaje</th>
            </tr>
        </thead>
        <tbody>
            <tr class="total">
                <td colspan="2">TOTAL</td>@foreach(['net','non_taxed','exempt','discount','iva','total'] as $k)<td class="right">{{ $m($report['summary'][$k]) }}</td>@endforeach<td></td>
            </tr>@forelse($report['rows'] as $r)<tr>
                <td>{{ $r['category'] }}</td>
                <td>{{ $r['subcategory'] }}</td>
                <td class="right">{{ $m($r['net']) }}</td>
                <td class="right">{{ $m($r['non_taxed']) }}</td>
                <td class="right">{{ $m($r['exempt']) }}</td>
                <td class="right">{{ $m($r['discount']) }}</td>
                <td class="right">{{ $m($r['iva']) }}</td>
                <td class="right">{{ $m($r['total']) }}</td>
                <td class="right">{{ number_format($r['percentage'],2,',','.') }}%</td>
            </tr>@empty<tr>
                <td colspan="9">Sin resultados.</td>
            </tr>@endforelse
        </tbody>
    </table>
</body>

</html>