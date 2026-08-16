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

    'paths' => ['api/*'],

    // POST es sólo para /api/eventos (analítica): el catálogo se sigue leyendo
    // con GET y no hay ningún otro endpoint de escritura.
    'allowed_methods' => ['GET', 'HEAD', 'OPTIONS', 'POST'],

    // Sólo el frontend Next.js. La API es pública pero de lectura, así que no
    // hace falta abrirla a cualquier origen.
    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'http://localhost:3000'),
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
