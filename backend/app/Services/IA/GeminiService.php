<?php

namespace App\Services\IA;

use Gemini;
use GuzzleHttp\Client as GuzzleClient;
use RuntimeException;
use Throwable;

class GeminiService
{
    public function generarTexto(string $instruccion): string
    {
        $apiKey = config('gemini.api_key');

        if (!$apiKey) {
            throw new RuntimeException('Falta configurar GEMINI_API_KEY en el archivo .env.');
        }

        $modelo = config('gemini.model', 'gemini-3.6-flash');
        $cliente = $this->crearCliente($apiKey);
        $maxRetries = max(0, (int) config('gemini.max_retries', 2));

        // PHP suele limitar cada petición a 30 segundos. Gemini puede tardar
        // más cuando está saturado y los reintentos deben terminar de forma
        // controlada para no cortar la conexión HTTP con el frontend.
        @set_time_limit(max(90, ((int) config('gemini.request_timeout', 60) * ($maxRetries + 1)) + 15));

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                $respuesta = $cliente->generativeModel($modelo)->generateContent($instruccion);
                $texto = trim($respuesta->text());

                if ($texto === '') {
                    throw new RuntimeException('Gemini devolvió una respuesta vacía.');
                }

                return $texto;
            } catch (Throwable $error) {
                report($error);
                $transient = $this->esErrorTransitorio($error);

                if ($transient && $attempt < $maxRetries) {
                    usleep((600 + ($attempt * 900)) * 1000);
                    continue;
                }

                if ($transient) {
                    throw new RuntimeException(
                        'Gemini está temporalmente saturado. Esperá unos segundos y volvé a intentarlo.',
                        previous: $error
                    );
                }

                throw new RuntimeException(
                    'No se pudo generar una respuesta con Gemini. Revisá la clave API, el modelo y los registros de Laravel.',
                    previous: $error
                );
            }
        }

        throw new RuntimeException('No se pudo generar una respuesta con Gemini.');
    }

    private function esErrorTransitorio(Throwable $error): bool
    {
        $message = mb_strtolower($error->getMessage());

        return str_contains($message, 'high demand')
            || str_contains($message, 'overload')
            || str_contains($message, 'temporarily unavailable')
            || str_contains($message, 'resource exhausted')
            || str_contains($message, 'rate limit')
            || str_contains($message, '429')
            || str_contains($message, '503');
    }

    private function crearCliente(string $apiKey): \Gemini\Client
    {
        $options = ['timeout' => (int) config('gemini.request_timeout', 60)];
        $verify = config('gemini.verify');

        if (is_string($verify) && $verify !== '' && is_file($verify)) {
            $options['verify'] = $verify;
        }

        $factory = Gemini::factory()->withApiKey($apiKey)->withHttpClient(new GuzzleClient($options));
        $baseUrl = config('gemini.base_url');

        if (is_string($baseUrl) && $baseUrl !== '') {
            $factory->withBaseUrl($baseUrl);
        }

        return $factory->make();
    }
}
