<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Adicione seus domínios aqui, ou use a variável de ambiente
        // explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:8080'))
        'http://localhost:3000',
        'http://localhost:8080',
        'https://meusite.com',
        'https://app.meusite.com',
        // Para desenvolvimento, você pode usar '*' mas NÃO em produção
        // '*'
    ],

    'allowed_origins_patterns' => [
        // Permite subdomínios específicos
        '/^https:\/\/.*\.meusite\.com$/',
        '/^http:\/\/localhost:\d+$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
