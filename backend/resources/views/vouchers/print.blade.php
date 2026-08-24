<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $voucher->name }}</title>
    <style>
        @page { size: 190mm 85mm; margin: 0; }
        * { box-sizing: border-box; }
        html, body { width: 190mm; height: 85mm; margin: 0; }
        body { font-family: Arial, Helvetica, sans-serif; color: #fff; background: #fff; }
        .voucher { width: 190mm; height: 85mm; display: grid; grid-template-columns: 68mm 122mm; overflow: hidden; border-radius: 5mm; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .benefit { background: #15151c; padding: 9mm 7mm; display: flex; flex-direction: column; justify-content: center; position: relative; }
        .benefit::after { content: ''; position: absolute; right: -2mm; top: 0; bottom: 0; width: 4mm; background: radial-gradient(circle at 0 3mm, transparent 2mm, #8c57ff 2.15mm) 0 0/4mm 6mm repeat-y; }
        .eyebrow { font-size: 4mm; font-weight: 700; letter-spacing: .7mm; text-transform: uppercase; }
        .amount { font-size: 15mm; line-height: .95; font-weight: 900; margin: 3mm 0; letter-spacing: -.7mm; }
        .benefit-label { border-top: .45mm solid rgba(255,255,255,.75); padding-top: 3mm; font-size: 3.4mm; text-transform: uppercase; }
        .content { background: linear-gradient(135deg, #7440dc, #9b62ff); padding: 7mm 8mm 6mm 10mm; display: flex; flex-direction: column; }
        .title { font-size: 7mm; line-height: 1.05; font-weight: 900; text-transform: uppercase; max-height: 15mm; overflow: hidden; }
        .message { font-size: 3.3mm; margin-top: 1.5mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-height: 4mm; }
        .codes { display: grid; grid-template-columns: 34mm 1fr; gap: 6mm; align-items: center; flex: 1; margin-top: 3mm; }
        .qr { width: 34mm; height: 34mm; background: #fff; padding: 1.5mm; border-radius: 1.5mm; }
        .barcode-wrap { background: #fff; color: #111; padding: 3mm 3mm 2mm; border-radius: 1.5mm; text-align: center; min-width: 0; }
        .barcode { width: 100%; height: 20mm; display: block; }
        .barcode svg { width: 100%; height: 100%; display: block; }
        .code { font-family: Consolas, monospace; font-size: 3.2mm; font-weight: 700; margin-top: 1.5mm; white-space: nowrap; }
        .footer { display: flex; justify-content: space-between; gap: 5mm; align-items: end; font-size: 2.8mm; margin-top: 2mm; }
        .status-copy { opacity: .9; }
        .print { position: fixed; top: 8px; right: 8px; z-index: 5; padding: 8px 14px; }
        @media print { .print { display: none; } }
    </style>
</head>
<body>
    <button class="print" onclick="window.print()">Imprimir</button>
    <main class="voucher">
        <section class="benefit">
            <div class="eyebrow">Voucher</div>
            <div class="amount">
                @if($voucher->value_type === 'percentage')
                    {{ rtrim(rtrim(number_format($voucher->amount, 2, '.', ''), '0'), '.') }}%
                @else
                    ${{ number_format($voucher->amount, 0, ',', '.') }}
                @endif
            </div>
            <div class="benefit-label">
                @if($voucher->value_type === 'percentage') Descuento en tu compra @else Crédito para tu compra @endif
            </div>
        </section>
        <section class="content">
            <div class="title">{{ $voucher->name }}</div>
            <div class="message">{{ $voucher->message ?: 'Presentá este voucher al momento de pagar.' }}</div>
            <div class="codes">
                <img class="qr" src="{{ $qrDataUri }}" alt="QR para consultar el voucher">
                <div class="barcode-wrap">
                    <div class="barcode">{!! $barcodeSvg !!}</div>
                    <div class="code">{{ $voucher->code }}</div>
                </div>
            </div>
            <div class="footer">
                <span>Válido hasta: {{ $voucher->expires_at?->format('d/m/Y') ?? 'Sin vencimiento' }}</span>
                @if($voucher->value_type === 'percentage' && $voucher->maximum_discount_amount)
                    <span>Tope: ${{ number_format($voucher->maximum_discount_amount, 0, ',', '.') }}</span>
                @endif
            </div>
        </section>
    </main>
    <script>
        window.addEventListener('load', () => setTimeout(() => window.print(), 400));
        window.addEventListener('afterprint', () => { if (window.opener) setTimeout(() => window.close(), 150); });
    </script>
</body>
</html>
