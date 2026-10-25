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

    // ✅ Inclure les routes de votre application et Sanctum
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'client/*', 'login', 'logout'],

    'allowed_methods' => ['*'],

    // ✅ Spécifier l'origine exacte de votre site (pas de wildcard '*')
    'allowed_origins' => [env('APP_URL', 'https://toucheat.onrender.com')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // ✅ Indispensable pour que les cookies de session soient envoyés
    'supports_credentials' => true,

];