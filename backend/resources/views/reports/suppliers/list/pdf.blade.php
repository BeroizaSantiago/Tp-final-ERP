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
    </style>
</head>

<body>
    <h1>Listado de Proveedores</h1>
    <table>
        <thead>
            <tr>
                <th>Proveedor</th>
                <th>Identificación</th>
                <th>Dirección</th>
                <th>Condición de Pago</th>
                <th>Teléfono</th>
                <th>E-mail</th>
            </tr>
        </thead>
        <tbody>@forelse($report['rows'] as $r)<tr>
                <td>{{ $r['provider'] }}</td>
                <td>{{ $r['identification'] }}</td>
                <td>{{ $r['address'] }}</td>
                <td>{{ $r['payment_condition'] }}</td>
                <td>{{ $r['phone'] }}</td>
                <td>{{ $r['email'] }}</td>
            </tr>@empty<tr>
                <td colspan="6">Sin proveedores registrados.</td>
            </tr>@endforelse</tbody>
    </table>
</body>

</html>