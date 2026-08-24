<?php

namespace App\Services\Printing;

use InvalidArgumentException;

class Code39Barcode
{
    private const PATTERNS = [
        '0'=>'101001101101','1'=>'110100101011','2'=>'101100101011','3'=>'110110010101','4'=>'101001101011',
        '5'=>'110100110101','6'=>'101100110101','7'=>'101001011011','8'=>'110100101101','9'=>'101100101101',
        'A'=>'110101001011','B'=>'101101001011','C'=>'110110100101','D'=>'101011001011','E'=>'110101100101',
        'F'=>'101101100101','G'=>'101010011011','H'=>'110101001101','I'=>'101101001101','J'=>'101011001101',
        'K'=>'110101010011','L'=>'101101010011','M'=>'110110101001','N'=>'101011010011','O'=>'110101101001',
        'P'=>'101101101001','Q'=>'101010110011','R'=>'110101011001','S'=>'101101011001','T'=>'101011011001',
        'U'=>'110010101011','V'=>'100110101011','W'=>'110011010101','X'=>'100101101011','Y'=>'110010110101',
        'Z'=>'100110110101','-'=>'100101011011','.'=>'110010101101',' '=>'100110101101','$'=>'100100100101',
        '/'=>'100100101001','+'=>'100101001001','%'=>'101001001001','*'=>'100101101101',
    ];

    public function svg(string $value, int $height = 72): string
    {
        $value = $this->normalize($value);
        $bits = $this->bits($value);

        $bars = '';
        foreach (str_split($bits) as $position => $bit) {
            if ($bit === '1') $bars .= '<rect x="'.$position.'" y="0" width="1" height="'.$height.'"/>';
        }

        $width = strlen($bits);
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$width.' '.$height.'" role="img" aria-label="Código de barras '.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'" preserveAspectRatio="none"><rect width="100%" height="100%" fill="#fff"/><g fill="#000">'.$bars.'</g></svg>';
    }

    public function bits(string $value): string
    {
        $value = $this->normalize($value);
        $bits = '0000000000';
        foreach (str_split('*'.$value.'*') as $character) $bits .= self::PATTERNS[$character].'0';
        return $bits.'0000000000';
    }

    public function pngDataUri(string $value, int $width = 900, int $height = 250): string
    {
        $bits = $this->bits($value);
        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        $quietZone = 25;
        $available = $width - ($quietZone * 2);
        $moduleWidth = $available / strlen($bits);
        foreach (str_split($bits) as $position => $bit) {
            if ($bit !== '1') continue;
            $x1 = $quietZone + (int) floor($position * $moduleWidth);
            $x2 = $quietZone + (int) ceil(($position + 1) * $moduleWidth) - 1;
            imagefilledrectangle($image, $x1, 8, $x2, $height - 8, $black);
        }

        ob_start();
        imagepng($image, null, 6);
        $png = (string) ob_get_clean();
        imagedestroy($image);
        return 'data:image/png;base64,'.base64_encode($png);
    }

    private function normalize(string $value): string
    {
        $value = strtoupper(trim($value));
        if ($value === '' || preg_match('/[^0-9A-Z. $\/+%\-]/', $value)) {
            throw new InvalidArgumentException('El valor no puede representarse como Code 39.');
        }
        return $value;
    }
}
