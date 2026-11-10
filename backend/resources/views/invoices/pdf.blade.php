<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de venta</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }

        h1 {
            margin-bottom: 4px;
            font-size: 20px;
        }

        .muted {
            color: #666;
        }

        .section {
            margin-top: 22px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 8px 6px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f2f2f2;
        }

        .number {
            text-align: right;
        }

        .total {
            margin-top: 18px;
            text-align: right;
            font-size: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Comprobante interno de venta</h1>

    <p>
        <strong>Número:</strong>
        {{ $invoice->display_number }}
        <br>
        <strong>Fecha:</strong>
        {{ $invoice->issue_date?->format('d/m/Y') ?? '—' }}
        <br>
        <strong>Cliente:</strong>
        {{ $invoice->client?->name ?? $invoice->customer_name ?? 'Consumidor final' }}
        <br>
        <strong>Email:</strong>
        {{ $invoice->client?->email ?? $invoice->customer_email ?? '—' }}
    </p>

    <div class="section">
        <strong>Detalle de la compra</strong>

        <table>
            <thead>
                <tr>
                    <th>Libro / producto</th>
                    <th class="number">Cantidad</th>
                    <th class="number">Precio unitario</th>
                    <th class="number">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td>
                            {{ $item->product_name ?? $item->description ?? 'Producto' }}
                        </td>
                        <td class="number">
                            {{ $item->quantity }}
                        </td>
                        <td class="number">
                            $ {{ number_format((float) ($item->unit_price_with_taxes ?? $item->unit_price ?? 0), 2, ',', '.') }}
                        </td>
                        <td class="number">
                            $ {{ number_format((float) ($item->total_amount ?? $item->subtotal_amount ?? 0), 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="total">
        Total: $ {{ number_format((float) $invoice->total_amount, 2, ',', '.') }}
        {{ $invoice->currency_name ?? 'Pesos' }}
    </div>

    <p class="section muted">
        Gracias por tu compra.
    </p>
</body>
</html>
