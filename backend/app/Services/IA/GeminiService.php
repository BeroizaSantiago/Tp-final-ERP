<?php

namespace App\Services\IA;

use Gemini;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class GeminiService
{
    private ?string $lastModel = null;

    public function generarTexto(string $instruccion, string $tipo = 'consulta_bibliografica'): string
    {
        $apiKey = config('gemini.api_key');
        if (!$apiKey) throw new RuntimeException('Falta configurar GEMINI_API_KEY en el archivo .env.');

        $models = $this->modelsFor($tipo);
        $eligible = $models->reject(fn ($model) => Cache::has($this->cooldownKey($model)))->values();
        if ($eligible->isEmpty()) $eligible = $models;

        $client = $this->crearCliente($apiKey);
        $maxRetries = max(0, (int) config('gemini.max_retries', 1));
        $timeout = (int) config('gemini.request_timeout', 30);
        @set_time_limit(max(90, ($timeout * ($maxRetries + 1) * max(1, $eligible->count())) + 20));
        $lastTransient = null;

        foreach ($eligible as $model) {
            for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
                try {
                    $response = $client->generativeModel($model)->generateContent($instruccion);
                    $text = trim($response->text());
                    if ($text === '') throw new RuntimeException('Gemini devolvió una respuesta vacía.');
                    Cache::forget($this->cooldownKey($model));
                    $this->lastModel = $model;
                    return $text;
                } catch (Throwable $error) {
                    report($error);
                    if ($this->esModeloNoDisponible($error)) {
                        Cache::put($this->cooldownKey($model), true, now()->addDay());
                        break;
                    }
                    if ($this->esCuotaAgotada($error)) {
                        throw new RuntimeException('La cuota de Gemini está agotada para esta clave API.', previous: $error);
                    }
                    if (!$this->esErrorTransitorio($error)) {
                        throw new RuntimeException('No se pudo generar una respuesta con Gemini. Revisá la clave API y los registros de Laravel.', previous: $error);
                    }

                    $lastTransient = $error;
                    if ($attempt < $maxRetries) usleep((600 + ($attempt * 900)) * 1000);
                }
            }
            Cache::put($this->cooldownKey($model), true, now()->addSeconds((int) config('gemini.model_cooldown', 120)));
        }

        throw new RuntimeException('Los modelos de Gemini están temporalmente saturados o no respondieron a tiempo.', previous: $lastTransient);
    }

    public function lastModel(): ?string
    {
        return $this->lastModel;
    }

    private function modelsFor(string $type)
    {
        $complex = in_array($type, ['consulta_bibliografica', 'recomendacion_personalizada'], true);
        $configured = config($complex ? 'gemini.models_complex' : 'gemini.models_simple', []);
        return collect($configured)->merge([config('gemini.model')])->merge(config('gemini.fallback_models', []))->filter()->unique()->values();
    }

    private function cooldownKey(string $model): string
    {
        return 'gemini:model-cooldown:'.sha1($model);
    }

    private function esErrorTransitorio(Throwable $error): bool
    {
        $message = mb_strtolower($error->getMessage());
        foreach (['high demand','overload','temporarily unavailable','resource exhausted','rate limit','timed out','timeout','curl error 28','429','503'] as $needle) if (str_contains($message, $needle)) return true;
        return false;
    }

    private function esModeloNoDisponible(Throwable $error): bool
    {
        $message = mb_strtolower($error->getMessage());
        return str_contains($message, 'no longer available') || str_contains($message, 'model not found') || str_contains($message, 'is not found');
    }

    private function esCuotaAgotada(Throwable $error): bool
    {
        $message = mb_strtolower($error->getMessage());
        return str_contains($message, 'exceeded your current quota') || str_contains($message, 'quota exceeded') || str_contains($message, 'check your plan and billing');
    }

    private function crearCliente(string $apiKey): \Gemini\Client
    {
        $options = ['timeout'=>(int)config('gemini.request_timeout',30), 'connect_timeout'=>10];
        $verify=config('gemini.verify');
        if(is_string($verify)&&$verify!==''&&is_file($verify))$options['verify']=$verify;
        $factory=Gemini::factory()->withApiKey($apiKey)->withHttpClient(new GuzzleClient($options));
        $baseUrl=config('gemini.base_url');
        if(is_string($baseUrl)&&$baseUrl!=='')$factory->withBaseUrl($baseUrl);
        return $factory->make();
    }
}
