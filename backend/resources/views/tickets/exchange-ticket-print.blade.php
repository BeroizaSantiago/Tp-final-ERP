{{-- Vista: Impresión de ticket de cambio. Genera el formato imprimible del ticket de cambio. --}}
@php
    $paperWidth = in_array(config('printing.thermal_paper_width_mm'), [58, 80], true)
        ? config('printing.thermal_paper_width_mm')
        : 80;
    $contentWidth = $paperWidth - 6;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket de Cambio</title>

    <style>
        @page { size: {{ $paperWidth }}mm auto; margin: 3mm; }
        * { box-sizing: border-box; }
        html {
            width: {{ $paperWidth }}mm;
            height: auto;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.35;
            width: {{ $contentWidth }}mm;
            margin: 0 auto;
            padding: 0 0 2mm;
            color: #000;
            background: #fff;
        }

        .center { text-align: center; }
        .line { border-top: 1px solid #000; margin: 10px 0; }
        .title { font-size: 18px; font-weight: bold; }
        .small { font-size: 11px; line-height: 1.3; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 0; vertical-align: top; }
        .qty { width: 35px; }
        .print-btn { margin: 15px 0; width: 100%; padding: 8px; }

        @media print {
            .print-btn { display: none; }
            html, body {
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
            }
            body { margin: 0; break-after: avoid-page; page-break-after: avoid; }
            * {
                color: #000 !important;
                text-shadow: none !important;
                filter: none !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        .qr-container {
            text-align: center;
            margin: 14px 0;
        }

        .qr-image {
            width: 135px;
            height: 135px;
            display: block;
            margin: 0 auto 5px;
        }
    </style>
</head>

<body>

<button class="print-btn" onclick="window.print()">Imprimir</button>

<div class="center">
    <div class="title">TICKET DE CAMBIO</div>
    <div class="small">{{ $ticket->ticket_number }}</div>
</div>

<div class="line"></div>

<strong>Venta:</strong><br>
{{ $ticket->invoice->full_number ?? $ticket->invoice->id }}<br>

<strong>Cliente:</strong><br>
{{ $ticket->invoice->customer_name ?? 'Consumidor Final' }}<br>

<strong>Fecha:</strong><br>
{{ optional($ticket->invoice->issue_date)->format('d/m/Y') ?? '-' }}<br>

<strong>Válido hasta:</strong><br>
{{ optional($ticket->expiration_date)->format('d/m/Y') ?? '-' }}

<div class="line"></div>

<table>
    @foreach($ticket->items as $item)
        <tr>
            <td class="qty">{{ $item->quantity }} x</td>
            <td>
                {{ $item->invoiceItem->product_name ?? $item->invoiceItem->description ?? 'Producto' }}

                @if($item->invoiceItem?->size_name || $item->invoiceItem?->color_name)
                    <br>
                    <span class="small">
                        Talle: {{ $item->invoiceItem->size_name ?? '-' }}
                        Color: {{ $item->invoiceItem->color_name ?? '-' }}
                    </span>
                @endif
            </td>
        </tr>
    @endforeach
</table>

<div class="line"></div>

<div class="qr-container">
    <img
        src="{{ $qrDataUri }}"
        alt="QR del ticket de cambio"
        class="qr-image"
    >

    <div class="small">
        Escaneá para validar el ticket
    </div>
</div>

<div class="line"></div>
<div class="center small">
    Conserve este comprobante.<br>
    El cambio está sujeto al estado del producto.<br>
    No válido para devolución de dinero.
</div>

<div class="line"></div>

<div class="center small">
    {{ config('app.name') }}
</div>

<script>
    let printDialogOpened = false;
    let auxiliaryWindowClosed = false;

    function closePrintWindow() {
        if (auxiliaryWindowClosed) return;
        auxiliaryWindowClosed = true;
        if (window.opener && !window.opener.closed) {
            setTimeout(() => window.close(), 150);
        }
    }

    window.addEventListener('afterprint', closePrintWindow);
    window.matchMedia?.('print').addEventListener?.('change', event => {
        if (printDialogOpened && !event.matches) closePrintWindow();
    });
    window.addEventListener('load', () => setTimeout(() => {
        printDialogOpened = true;
        window.print();
    }, 500));
</script>

</body>
</html>
