<?php

namespace App\Services\IA;

use Gemini\Laravel\Facades\Gemini;
use RuntimeException;
use Throwable;

class GeminiService
{
    public function generarTexto(string $instruccion): string
    {
        if (!config('gemini.api_key')) {
            throw new RuntimeException(
                'Falta configurar GEMINI_API_KEY en el archivo .env.'
            );
        }

        $modelo = config('gemini.model', 'gemini-2.5-flash');

        try {
            $respuesta = Gemini::generativeModel($modelo)
                ->generateContent($instruccion);

            $texto = trim($respuesta->text());

            if ($texto === '') {
                throw new RuntimeException(
                    'Gemini devolvió una respuesta vacía.'
                );
            }

            return $texto;
        } catch (Throwable $e) {
            report($e);

            throw new RuntimeException(
                'No se pudo generar una respuesta con Gemini. '
                . 'Revisá la clave API, el modelo y los registros de Laravel.',
                previous: $e
            );
        }
    }
}
