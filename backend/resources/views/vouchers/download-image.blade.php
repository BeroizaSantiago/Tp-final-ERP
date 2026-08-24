<!doctype html>
<html lang="es">
@php
$downloadData = [
'code' => $voucher->code,
'name' => $voucher->name,
'message' => $voucher->message ?: 'Presentá este voucher al momento de pagar.',
'benefit' => $voucher->value_type === 'percentage'
? rtrim(rtrim(number_format($voucher->amount, 2, '.', ''), '0'), '.').' %'
: '$ '.number_format($voucher->amount, 0, ',', '.'),
'benefit_label' => $voucher->value_type === 'percentage' ? 'DESCUENTO EN TU COMPRA' : 'CRÉDITO PARA TU COMPRA',
'expiration' => $voucher->expires_at?->format('d/m/Y') ?? 'Sin vencimiento',
'maximum' => $voucher->value_type === 'percentage' && $voucher->maximum_discount_amount
? '$ '.number_format($voucher->maximum_discount_amount, 0, ',', '.')
: null,
'qr' => $qrDataUri,
'barcode' => $barcodeBits,
];
@endphp

<head>
    <meta charset="utf-8">
    <title>Descargando {{ $voucher->code }}</title>
</head>

<body style="margin:0;background:#171724;color:#fff;font-family:Arial,sans-serif;text-align:center;padding:30px">
    <p id="status">Preparando voucher JPG...</p>
    <canvas id="voucher" width="1900" height="850" style="position:fixed;left:-10000px;top:0;width:1px;height:1px"></canvas>
    <script>
        const data = @json($downloadData);
        const canvas = document.getElementById('voucher'),
            ctx = canvas.getContext('2d');

        function fit(text, maxWidth, startSize, weight = '700') {
            let size = startSize;
            do {
                ctx.font = `${weight} ${size}px Arial`;
                if (ctx.measureText(text).width <= maxWidth) break;
                size -= 2
            } while (size > 20);
            return size;
        }

        function draw() {
            ctx.fillStyle = '#15151c';
            ctx.fillRect(0, 0, 680, 850);
            const gradient = ctx.createLinearGradient(680, 0, 1900, 850);
            gradient.addColorStop(0, '#7440dc');
            gradient.addColorStop(1, '#9b62ff');
            ctx.fillStyle = gradient;
            ctx.fillRect(680, 0, 1220, 850);
            ctx.fillStyle = '#fff';
            ctx.font = '700 34px Arial';
            ctx.fillText('VOUCHER', 70, 170);
            ctx.font = `900 ${fit(data.benefit,540,128,'900')}px Arial`;
            ctx.fillText(data.benefit, 70, 390);
            ctx.fillRect(70, 455, 530, 3);
            ctx.font = '700 24px Arial';
            ctx.fillText(data.benefit_label, 70, 515);
            const title = data.name.toUpperCase();
            ctx.font = `900 ${fit(title,1050,54,'900')}px Arial`;
            ctx.fillText(title, 770, 105);
            ctx.font = `400 ${fit(data.message,1050,24,'400')}px Arial`;
            ctx.fillText(data.message, 770, 160);
            ctx.fillStyle = '#fff';
            ctx.fillRect(770, 220, 370, 370);
            ctx.drawImage(qr, 785, 235, 340, 340);
            ctx.fillRect(1180, 220, 640, 370);
            ctx.fillStyle = '#15151c';
            const module = 580 / data.barcode.length;
            const width = data.barcode.length * module,
                start = 1180 + (640 - width) / 2;
            [...data.barcode].forEach((bit, i) => {
                if (bit === '1') {
                    const x1 = Math.floor(start + i * module),
                        x2 = Math.ceil(start + (i + 1) * module);
                    ctx.fillRect(x1, 245, Math.max(1, x2 - x1), 250)
                }
            });
            ctx.font = `700 ${fit(data.code,590,24,'700')}px monospace`;
            ctx.textAlign = 'center';
            ctx.fillText(data.code, 1500, 545);
            ctx.textAlign = 'left';
            ctx.fillStyle = '#fff';
            ctx.font = '400 20px Arial';
            ctx.fillText(`Válido hasta: ${data.expiration}`, 770, 700);
            if (data.maximum) ctx.fillText(`Tope máximo: ${data.maximum}`, 770, 745);
            ctx.font = '400 18px Arial';
            canvas.toBlob(blob => {
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `voucher-${data.code}.jpg`;
                link.click();
                setTimeout(() => {
                    URL.revokeObjectURL(link.href);
                    document.getElementById('status').textContent = 'Voucher descargado.';
                    window.close()
                }, 1200)
            }, 'image/jpeg', .92);
        }
        const qr = new Image();
        qr.onload = draw;
        qr.onerror = () => document.getElementById('status').textContent = 'No se pudo preparar el QR.';
        qr.src = data.qr;
    </script>
</body>

</html>