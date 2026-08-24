{{-- Vista: PDF del Listado general de Compras. --}}
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
            font-family: DejaVu Sans, sans-serif;
            color: #312d4b;
            font-size: 8px
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
            margin-top: 10px
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

<body>@php($m=fn($v)=>'$ '.number_format($v,2,',','.'))<h1>Listado de Compras</h1>
    <div class="meta">{{ $report['filters']['date_from'] }} al {{ $report['filters']['date_to'] }}</div>
    <table>
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Número</th>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>Provincia</th>
                <th class="right">Neto</th>
                <th class="right">No Gravado</th>
                <th class="right">Exento</th>
                <th class="right">Recargo</th>
                <th class="right">Descuento</th>
                <th class="right">IVA</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr class="total">
                <td colspan="5">TOTAL</td>@foreach(['net','non_taxed','exempt','surcharge','discount','iva','total'] as $k)<td class="right">{{ $m($report['summary'][$k]) }}</td>@endforeach
            </tr>@forelse($report['rows'] as $r)<tr>
                <td>{{ $r['type'] }}</td>
                <td>{{ $r['number'] }}</td>
                <td>{{ $r['date'] }}</td>
                <td>{{ $r['provider'] }}</td>
                <td>{{ $r['province'] }}</td>
                <td class="right">{{ $m($r['net']) }}</td>
                <td class="right">{{ $m($r['non_taxed']) }}</td>
                <td class="right">{{ $m($r['exempt']) }}</td>
                <td class="right">{{ $m($r['surcharge']) }}</td>
                <td class="right">{{ $m($r['discount']) }}</td>
                <td class="right">{{ $m($r['iva']) }}</td>
                <td class="right">{{ $m($r['total']) }}</td>
            </tr>@empty<tr>
                <td colspan="12">Sin resultados.</td>
            </tr>@endforelse
        </tbody>
    </table>
</body>

</html>