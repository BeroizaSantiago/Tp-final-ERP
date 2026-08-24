<?php

namespace App\Services\Printing;

use App\Models\Sales\Voucher;
use RuntimeException;

class VoucherImage
{
    public function __construct(private Code39Barcode $barcode) {}

    public function png(Voucher $voucher, string $qrPng): string
    {
        $canvas = imagecreatetruecolor(1900, 850);
        if (! $canvas) throw new RuntimeException('No se pudo crear la imagen del voucher.');

        $white = imagecolorallocate($canvas, 255, 255, 255);
        $black = imagecolorallocate($canvas, 21, 21, 28);
        $purple = imagecolorallocate($canvas, 140, 87, 255);
        imagefilledrectangle($canvas, 0, 0, 679, 849, $black);
        imagefilledrectangle($canvas, 680, 0, 1899, 849, $purple);

        $font = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');
        $bold = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf');
        $mono = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSansMono-Bold.ttf');

        $this->drawText($canvas, 'VOUCHER', 34, 70, 170, $white, $bold);
        $benefit = $voucher->value_type === 'percentage'
            ? rtrim(rtrim(number_format((float) $voucher->amount, 2, '.', ''), '0'), '.').' %'
            : '$ '.number_format((float) $voucher->amount, 0, ',', '.');
        $this->fitText($canvas, $benefit, 128, 48, 70, 390, 540, $white, $bold);
        imageline($canvas, 70, 455, 600, 455, $white);
        $label = $voucher->value_type === 'percentage' ? 'DESCUENTO EN TU COMPRA' : 'CRÉDITO PARA TU COMPRA';
        $this->drawText($canvas, $label, 24, 70, 515, $white, $bold, 540);

        $this->fitText($canvas, mb_strtoupper($voucher->name), 54, 30, 770, 105, 1050, $white, $bold);
        $message = $voucher->message ?: 'Presentá este voucher al momento de pagar.';
        $this->fitText($canvas, $message, 24, 17, 770, 160, 1050, $white, $font);

        $qr = @imagecreatefromstring($qrPng);
        if (! $qr) throw new RuntimeException('No se pudo generar el QR del voucher.');
        imagefilledrectangle($canvas, 770, 220, 1139, 589, $white);
        imagecopyresampled($canvas, $qr, 785, 235, 0, 0, 340, 340, imagesx($qr), imagesy($qr));
        imagedestroy($qr);

        imagefilledrectangle($canvas, 1180, 220, 1819, 589, $white);
        $bits = $this->barcode->bits($voucher->code);
        $module = 580 / strlen($bits);
        $barcodeWidth = strlen($bits) * $module;
        $startX = 1180 + (640 - $barcodeWidth) / 2;
        foreach (str_split($bits) as $position => $bit) {
            if ($bit === '1') imagefilledrectangle($canvas, (int) floor($startX + $position * $module), 245, (int) ceil($startX + ($position + 1) * $module) - 1, 495, $black);
        }
        $this->centerText($canvas, $voucher->code, 20, 1180, 640, 545, $black, $mono);

        $expiration = 'Válido hasta: '.($voucher->expires_at?->format('d/m/Y') ?? 'Sin vencimiento');
        $this->drawText($canvas, $expiration, 20, 770, 700, $white, $font, 1000);
        if ($voucher->value_type === 'percentage' && $voucher->maximum_discount_amount) {
            $this->drawText($canvas, 'Tope máximo: $ '.number_format((float) $voucher->maximum_discount_amount, 0, ',', '.'), 20, 770, 745, $white, $font, 1000);
        }
        $this->drawText($canvas, 'QR: consultar estado  ·  Código de barras: cobrar', 18, 770, 795, $white, $font, 1000);

        ob_start();
        imagepng($canvas, null, 6);
        $png = (string) ob_get_clean();
        imagedestroy($canvas);
        return $png;
    }

    private function fitText($image, string $text, int $size, int $minimum, int $x, int $baseline, int $maximumWidth, int $color, string $font): void
    {
        $this->drawBitmapText($image, $text, $size, $x, $baseline, $maximumWidth, $color);
    }

    private function centerText($image, string $text, int $size, int $x, int $width, int $baseline, int $color, string $font): void
    {
        $plain = $this->plainText($text);
        $baseWidth = max(1, strlen($plain) * imagefontwidth(5));
        $scale = max(1, min(5, (int) floor(($width - 20) / $baseWidth)));
        $renderedWidth = $baseWidth * $scale;
        $this->drawBitmapText($image, $plain, $size, $x + intdiv($width - $renderedWidth, 2), $baseline, $width - 20, $color, $scale);
    }

    private function drawText($image, string $text, int $size, int $x, int $baseline, int $color, string $font, ?int $maximumWidth = null): void
    {
        $this->drawBitmapText($image, $text, $size, $x, $baseline, $maximumWidth ?? 1000, $color);
    }

    private function drawBitmapText($image, string $text, int $size, int $x, int $baseline, int $maximumWidth, int $color, ?int $forcedScale = null): void
    {
        $text = $this->plainText($text);
        $font = 5;
        $baseWidth = max(1, strlen($text) * imagefontwidth($font));
        $baseHeight = imagefontheight($font);
        $scale = $forcedScale ?? max(1, min(8, (int) round($size / $baseHeight)));
        while ($scale > 1 && $baseWidth * $scale > $maximumWidth) $scale--;

        $temporary = imagecreatetruecolor($baseWidth, $baseHeight);
        imagealphablending($temporary, false);
        imagesavealpha($temporary, true);
        $transparent = imagecolorallocatealpha($temporary, 0, 0, 0, 127);
        imagefill($temporary, 0, 0, $transparent);
        $rgb = imagecolorsforindex($image, $color);
        $temporaryColor = imagecolorallocate($temporary, $rgb['red'], $rgb['green'], $rgb['blue']);
        imagestring($temporary, $font, 0, 0, $text, $temporaryColor);

        imagecopyresampled($image, $temporary, $x, $baseline - $baseHeight * $scale, 0, 0, $baseWidth * $scale, $baseHeight * $scale, $baseWidth, $baseHeight);
        imagedestroy($temporary);
    }

    private function plainText(string $text): string
    {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        return $converted !== false ? $converted : preg_replace('/[^\x20-\x7E]/', '', $text);
    }
}
