@php
    $isInternal = (bool) $invoice->is_internal_receipt;
    $storedLetter = strtoupper(trim((string) $invoice->letter));
    $letter = in_array($storedLetter, ['A', 'B', 'C'], true) ? $storedLetter : 'B';
    $documentTitle = $isInternal ? 'Factura '.$letter.' ' : ($invoice->receipt_type_name ?: 'Factura').' '.$letter;
    $pointOfSale = str_pad((string) ($invoice->arca_point_of_sale ?: $invoice->first_number ?: 0), 5, '0', STR_PAD_LEFT);
    $number = $invoice->display_number ?: $invoice->full_number ?: $invoice->id;
    $companyName = config('arca.issuer_name', config('app.name'));
    $companyCuit = config('arca.cuit', '-');
    $companyVat = config('arca.vat_condition', 'Responsable Inscripto');
    $demoCae = '86'.str_pad((string) $invoice->id, 12, '0', STR_PAD_LEFT);
    $displayCae = $invoice->arca_cae ?: $demoCae;
    $displayCaeExpiration = $invoice->arca_cae_expiration
        ?: optional($invoice->issue_date)->copy()?->addDays(10);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentTitle }} {{ $number }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; font-family: Helvetica, Arial, sans-serif; font-size: 10px; line-height: 1.25; }
        .box { border: 1px solid #333; margin-bottom: 10px; padding: 10px; }
        .header-table, .data-table, .summary-table, .fiscal-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .company { width: 43%; }
        .letter-cell { width: 14%; text-align: center; }
        .document { width: 43%; padding-left: 12px; }
        .company-name, .document-title { margin: 0 0 9px; font-size: 20px; font-weight: bold; }
        .letter-box { display: inline-block; width: 54px; border: 2px solid #111; padding: 8px 0; font-size: 28px; font-weight: bold; }
        .letter-code { margin-top: 3px; font-size: 9px; color: #555; }
        .original { display: inline-block; border: 1px solid #333; margin-bottom: 10px; padding: 3px 9px; font-weight: bold; }
        .label { font-weight: bold; }
        .section-title { margin: 0 0 8px; font-size: 13px; font-weight: bold; }
        .data-table td { width: 50%; padding: 1px 0; vertical-align: top; }
        .products { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .products th, .products td { border: 1px solid #555; padding: 6px; vertical-align: top; }
        .products th { background: #f2f2f2; text-align: center; font-weight: bold; }
        .products .code { width: 13%; }
        .products .description { width: 39%; }
        .products .qty { width: 9%; }
        .products .price { width: 13%; }
        .products .discount { width: 11%; }
        .products .total { width: 15%; }
        .right { text-align: right; }
        .center { text-align: center; }
        .item-name { font-weight: bold; }
        .item-detail { margin-top: 2px; color: #444; font-size: 9px; }
        .summary-wrap { width: 42%; margin: 10px 0 12px auto; border: 1px solid #333; padding: 9px; }
        .summary-table td { padding: 4px 0; }
        .summary-total td { border-top: 1px solid #777; padding-top: 6px; font-size: 14px; font-weight: bold; }
        .fiscal { min-height: 145px; }
        .fiscal-table td { vertical-align: top; }
        .qr-cell { width: 27%; text-align: center; }
        .qr-frame { display: inline-block; padding: 5px; background: #fff; }
        .qr-grid { border-collapse: collapse; border-spacing: 0; }
        .qr-grid td { width: 2.6px; height: 2.6px; padding: 0; border: 0; font-size: 0; line-height: 0; }
        .qr-grid .black { background: #000; }
        .qr-grid .white { background: #fff; }
        .fiscal-data { width: 73%; padding-left: 14px; }
        .cae { margin: 2px 0 8px; font-size: 17px; font-weight: bold; }
        .internal-message { padding-top: 30px; text-align: center; }
        .internal-message strong { display: block; margin-bottom: 8px; font-size: 17px; }
        .footer-note { margin-top: 8px; color: #555; font-size: 9px; }
    </style>
</head>
<body>
    <div class="box">
        <table class="header-table">
            <tr>
                <td class="company">
                    <div class="company-name">{{ $companyName }}</div>
                    <div><span class="label">Razón social:</span> {{ $companyName }}</div>
                    <div><span class="label">CUIT:</span> {{ $companyCuit }}</div>
                    <div><span class="label">Condición IVA:</span> {{ $companyVat }}</div>
                    @if(config('arca.issuer_address'))<div><span class="label">Domicilio comercial:</span> {{ config('arca.issuer_address') }}</div>@endif
                    @if(config('arca.activity_start'))<div><span class="label">Inicio de actividades:</span> {{ config('arca.activity_start') }}</div>@endif
                </td>
                <td class="letter-cell">
                    <div class="letter-box">{{ $letter }}</div>
                    <div class="letter-code">{{ $isInternal ? 'COD. 6 - DEMO' : 'COD. '.($invoice->arca_voucher_type ?: '-') }}</div>
                </td>
                <td class="document">
                    <div class="document-title">{{ $documentTitle }}</div>
                    <div class="original">ORIGINAL</div>
                    <div><span class="label">Punto de venta:</span> {{ $pointOfSale }}</div>
                    <div><span class="label">Comprobante N.º:</span> {{ $number }}</div>
                    <div><span class="label">Fecha de emisión:</span> {{ optional($invoice->issue_date)->format('d/m/Y H:i') ?? '-' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <div class="section-title">Datos del cliente</div>
        <table class="data-table">
            <tr>
                <td><span class="label">Cliente:</span> {{ $invoice->client?->name ?? $invoice->customer_name ?? 'Consumidor final' }}</td>
                <td><span class="label">Condición de venta:</span> {{ $invoice->payment_condition_name ?? 'CONTADO' }}</td>
            </tr>
            <tr>
                <td><span class="label">Documento/CUIT:</span> {{ $invoice->client?->document_number ?? '-' }}</td>
                <td><span class="label">Moneda:</span> {{ $invoice->currency_name ?? 'Pesos' }}</td>
            </tr>
            <tr>
                <td><span class="label">Email:</span> {{ $invoice->client?->email ?? $invoice->customer_email ?? '-' }}</td>
                <td><span class="label">Estado:</span> {{ $invoice->status_name ?? 'Cobrada' }}</td>
            </tr>
            <tr>
                <td><span class="label">Teléfono:</span> {{ $invoice->client?->phone_number ?? $invoice->customer_phone_number ?? '-' }}</td>
                <td><span class="label">Vendedor:</span> {{ $invoice->seller_full_name ?? $invoice->user_name ?? 'Sistema' }}</td>
            </tr>
        </table>
    </div>

    <table class="products">
        <thead>
            <tr><th class="code">Código</th><th class="description">Producto</th><th class="qty">Cant.</th><th class="price">Precio</th><th class="discount">Desc.</th><th class="total">Total</th></tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $item)
                <tr>
                    <td>{{ $item->product_code ?: $item->product_barcode ?: '-' }}</td>
                    <td>
                        <div class="item-name">{{ $item->product_name ?? $item->description ?? 'Producto' }}</div>
                        @if($item->description && $item->description !== $item->product_name)<div class="item-detail">{{ $item->description }}</div>@endif
                    </td>
                    <td class="right">{{ number_format((float) $item->quantity, 2, ',', '.') }}</td>
                    <td class="right">${{ number_format((float) ($item->unit_price_with_taxes ?? $item->unit_price ?? 0), 2, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) ($item->discount_percentage ?? 0), 2, ',', '.') }}%</td>
                    <td class="right">${{ number_format((float) ($item->total_amount ?? $item->subtotal_amount ?? 0), 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="center">Sin productos registrados</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-wrap">
        <table class="summary-table">
            <tr><td class="label">Subtotal gravado:</td><td class="right">${{ number_format((float) ($invoice->taxed_amount ?? 0), 2, ',', '.') }}</td></tr>
            <tr><td class="label">IVA:</td><td class="right">${{ number_format((float) ($invoice->tax_amount ?? 0), 2, ',', '.') }}</td></tr>
            @if((float) ($invoice->promotion_discount_amount ?? 0) > 0)
                <tr><td class="label">Descuento:</td><td class="right">-${{ number_format((float) $invoice->promotion_discount_amount, 2, ',', '.') }}</td></tr>
            @endif
            <tr class="summary-total"><td>TOTAL:</td><td class="right">${{ number_format((float) $invoice->total_amount, 2, ',', '.') }}</td></tr>
        </table>
    </div>

    <div class="box fiscal">
        <table class="fiscal-table"><tr>
            <td class="qr-cell">
                <div class="qr-frame">
                    <table class="qr-grid">
                        @foreach($qrMatrix as $qrRow)
                            <tr>@foreach($qrRow as $qrBlock)<td class="{{ $qrBlock ? 'black' : 'white' }}"></td>@endforeach</tr>
                        @endforeach
                    </table>
                </div>
            </td>
            <td class="fiscal-data">
                <div class="cae">CAE{{ $isInternal ? : '' }}: {{ $displayCae }}</div>
                <div><span class="label">Vencimiento CAE:</span> {{ optional($displayCaeExpiration)->format('d/m/Y') ?? '-' }}</div>
                <p class="label">Referencia electrónica</p>
                @if($isInternal)
                @else
                    <p class="footer-note">Escanee el código QR para verificar la validez fiscal del comprobante.</p>
                @endif
            </td>
        </tr></table>
    </div>
</body>
</html>
