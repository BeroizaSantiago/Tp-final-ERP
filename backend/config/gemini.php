<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Gemini API Key
    |--------------------------------------------------------------------------
    |
    | Here you may specify your Gemini API Key and organization. This will be
    | used to authenticate with the Gemini API - you can find your API key
    | on Google AI Studio, at https://aistudio.google.com/app/apikey.
    */

    'api_key' => env('GEMINI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Gemini Base URL
    |--------------------------------------------------------------------------
    |
    | If you need a specific base URL for the Gemini API, you can provide it here.
    | Otherwise, leave empty to use the default value.
    */
    'base_url' => env('GEMINI_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout may be used to specify the maximum number of seconds to wait
    | for a response. Gemini puede tardar más de 30 segundos cuando el contexto
    | es grande, así que el valor por defecto es 60.
    */

    'request_timeout' => env('GEMINI_REQUEST_TIMEOUT', 60),

    'max_retries' => env('GEMINI_MAX_RETRIES', 2),

    /*
    |--------------------------------------------------------------------------
    | CA Bundle
    |--------------------------------------------------------------------------
    |
    | PHP/cURL on Windows may not have a CA bundle configured, which causes
    | "cURL error 60" when connecting to Google. The project ships a bundle
    | so HTTPS requests work without changing the global php.ini.
    |
    */

    'verify' => env('GEMINI_VERIFY', base_path('resources/certs/cacert.pem')),

    'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
];
