@php
    $paperWidth = in_array(config('printing.thermal_paper_width_mm'), [58, 80], true)
        ? config('printing.thermal_paper_width_mm')
        : 80;
    $contentWidth = $paperWidth - 6;
    $isInternal = $invoice->is_internal_receipt;

    $shortCardName = static function (?string $name): ?string {
        $name = trim((string) $name);
        if ($name === '') return null;

        $normalized = mb_strtolower(\Illuminate\Support\Str::ascii($name));

        if (in_array($normalized, ['debito', 'credito', 'tarjeta'], true)) {
            return null;
        }

        return match (true) {
            str_contains($normalized, 'mastercard'), str_contains($normalized, 'master card') => 'Masterc.',
            str_contains($normalized, 'mercado pago') => 'M. Pago',
            str_contains($normalized, 'american express') => 'Amex',
            default => \Illuminate\Support\Str::limit($name, 10, '.'),
        };
    };

    $paymentDescription = static function ($payment) use ($shortCardName): string {
        $method = (string) $payment->payment_method;
        $card = $shortCardName($payment->card_name);

        return match ($method) {
            'cash' => 'Contado',
            'debit_card' => 'Tarjeta débito'.($card ? ' · '.$card : ''),
            'credit_card' => 'Tarjeta crédito'.($card ? ' · '.$card : '')
                .($payment->card_plan ? ' P.'.$payment->card_plan : ''),
            'transfer' => 'Transferencia'.($payment->bank_name
                ? ' · '.\Illuminate\Support\Str::limit($payment->bank_name, 13, '.')
                : ''),
            'mercado_pago_qr' => 'Mercado Pago QR',
            'voucher' => 'Voucher'.($payment->voucher?->code ? ' · '.$payment->voucher->code : ''),
            'current_account' => 'Cuenta corriente',
            default => str((string) $method)->replace('_', ' ')->title()->toString(),
        };
    };
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $isInternal ? 'Ticket de venta' : 'Factura '.$invoice->letter }} - {{ $invoice->display_number ?: $invoice->id }}</title>
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
            width: {{ $contentWidth }}mm;
            margin: 0 auto;
            padding: 0 0 2mm;
            color: #000;
            background: #fff;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.35;
            -webkit-font-smoothing: none;
            text-rendering: optimizeLegibility;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .strong { font-weight: 700; }
        .title { font-size: 17px; font-weight: 700; }
        .letter { font-size: 27px; font-weight: 700; line-height: 1; margin: 5px 0; }
        .line { border-top: 1px solid #000; margin: 7px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; }
        .row span:last-child { text-align: right; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 3px 0; vertical-align: top; color: #000; }
        th { border-bottom: 1px solid #000; text-align: left; font-weight: 700; }
        .qty { width: 13%; }
        .amount { width: 28%; text-align: right; }
        .detail { font-size: 11px; line-height: 1.3; }
        .total { font-size: 15px; font-weight: 700; }
        .qr { display: block; width: 38mm; height: 38mm; margin: 5px auto; }
        .no-print {
            display: block;
            width: 100%;
            margin: 10px 0;
            padding: 8px;
            cursor: pointer;
        }
        @media print {
            .no-print { display: none; }
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
    </style>
</head>
<body>
    <button type="button" class="no-print" onclick="window.print()">Imprimir {{ $isInternal ? 'ticket' : 'factura' }}</button>

    <header class="center">
        @if($isInternal)
            <div class="title">{{ config('app.name') }}</div>
            <div class="strong">TICKET DE VENTA</div>
        @else
            <div class="title">{{ config('arca.issuer_name', config('app.name')) }}</div>
            <div>{{ config('arca.issuer_address', '') }}</div>
            <div>CUIT: {{ config('arca.cuit', '-') }}</div>
            <div>Condición IVA: {{ config('arca.vat_condition', 'MONOTRIBUTO') }}</div>
            @if(config('arca.gross_income'))
                <div>Ingresos Brutos: {{ config('arca.gross_income') }}</div>
            @endif
            @if(config('arca.activity_start'))
                <div>Inicio de actividades: {{ config('arca.activity_start') }}</div>
            @endif
            <div class="letter">{{ $invoice->letter ?? 'C' }}</div>
            <div class="strong">{{ $invoice->receipt_type_name ?? 'Factura' }} - ORIGINAL</div>
        @endif
    </header>

    <div class="line"></div>
    <div class="row">
        <span>Comprobante:</span>
        <span>{{ $isInternal ? $invoice->display_number : ($invoice->full_number ?: str_pad((string) ($invoice->first_number ?? 0), 4, '0', STR_PAD_LEFT) . '-' . str_pad((string) ($invoice->arca_voucher_number ?? $invoice->id), 8, '0', STR_PAD_LEFT)) }}</span>
    </div>
    <div class="row">
        <span>Fecha:</span>
        <span>{{ optional($invoice->issue_date)->format('d/m/Y H:i') }}</span>
    </div>
    <div class="row">
        <span>Vendedor:</span>
        <span>{{ $invoice->seller_full_name ?? $invoice->user_name ?? '-' }}</span>
    </div>

    <div class="line"></div>
    <div class="strong">{{ $invoice->customer_name ?: 'Consumidor Final' }}</div>
    @if($invoice->client?->document_number)
        <div>Documento/CUIT: {{ $invoice->client->document_number }}</div>
    @endif
    @if(!$isInternal && $invoice->client?->vat_classification)
        <div>Condición IVA: {{ $invoice->client->vat_classification }}</div>
    @endif
    <div>Condición de venta: {{ $invoice->payment_condition_name ?? 'CONTADO' }}</div>

    <div class="line"></div>
    <table>
        <thead>
            <tr>
                <th class="qty">Cant.</th>
                <th>Producto</th>
                <th class="amount">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td class="qty">{{ number_format((float) $item->quantity, 2, ',', '.') }}</td>
                    <td>
                        {{ $item->product_name ?? $item->description }}
                        @if($item->size_name || $item->color_name)
                            <div class="detail">
                                {{ $item->size_name ? 'Talle: '.$item->size_name : '' }}
                                {{ $item->color_name ? ' Color: '.$item->color_name : '' }}
                            </div>
                        @endif
                        <div class="detail">
                            {{ $item->product_code ?: $item->product_barcode }}
                            · ${{ number_format((float) $item->unit_price_with_taxes, 2, ',', '.') }}
                            @if((float) $item->discount_percentage > 0)
                                · Desc. {{ number_format((float) $item->discount_percentage, 2, ',', '.') }}%
                            @endif
                        </div>
                    </td>
                    <td class="amount">${{ number_format((float) $item->total_amount, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>
    @unless($isInternal)
        <div class="row"><span>Subtotal gravado:</span><span>${{ number_format((float) $invoice->taxed_amount, 2, ',', '.') }}</span></div>
        <div class="row"><span>IVA:</span><span>${{ number_format((float) $invoice->tax_amount, 2, ',', '.') }}</span></div>
        @if((float) $invoice->gross_income_perception_amount > 0)
            <div class="row"><span>Percepciones:</span><span>${{ number_format((float) $invoice->gross_income_perception_amount, 2, ',', '.') }}</span></div>
        @endif
    @endunless
    <div class="row total"><span>TOTAL:</span><span>${{ number_format((float) $invoice->total_amount, 2, ',', '.') }}</span></div>

    @if($invoice->payments->isNotEmpty())
        <div class="line"></div>
        <div class="strong">Pagos</div>
        @foreach($invoice->payments as $payment)
            <div class="row">
                <span>{{ $paymentDescription($payment) }}</span>
                <span>${{ number_format((float) ($payment->total_paid ?: $payment->amount), 2, ',', '.') }}</span>
            </div>
            @if($payment->payment_method === 'transfer' && $payment->reference)
                <div class="detail">Ref.: {{ \Illuminate\Support\Str::limit($payment->reference, 24, '.') }}</div>
            @endif
        @endforeach
    @endif

    <div class="line"></div>
    @if(!$isInternal && $invoice->arca_cae)
        <div class="center strong">Factura Electrónica</div>
        <div>CAE: {{ $invoice->arca_cae }}</div>
        <div>Vencimiento CAE: {{ optional($invoice->arca_cae_expiration)->format('d/m/Y') }}</div>
        @if($qrDataUri)
            <img class="qr" src="{{ $qrDataUri }}" alt="QR ARCA">
        @endif
    @elseif(!$isInternal)
        <div class="center strong">FACTURA PENDIENTE DE AUTORIZACIÓN</div>
        <div class="center">Este ejemplar todavía no posee validez fiscal.</div>
    @endif

    <div class="line"></div>
    <div class="center">Gracias por su compra</div>

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
        }, 350));
    </script>
</body>
</html>
