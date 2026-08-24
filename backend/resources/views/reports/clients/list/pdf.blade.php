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
    </style>
</head>

<body>
    <h1>Listado de Clientes</h1>
    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Identificación</th>
                <th>Dirección</th>
                <th>Condición de Pago</th>
                <th>Teléfono</th>
                <th>E-mail</th>
                <th>Condición frente al IVA</th>
                <th>Fecha de Nacimiento</th>
                <th>Referido</th>
            </tr>
        </thead>
        <tbody>@forelse($report['rows'] as $r)<tr>@foreach($r as $v)<td>{{ $v }}</td>@endforeach</tr>@empty<tr>
                <td colspan="9">Sin clientes.</td>
            </tr>@endforelse</tbody>
    </table>
</body>

</html>