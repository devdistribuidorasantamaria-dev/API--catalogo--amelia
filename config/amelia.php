<?php

return [

    // URL del frontend Next.js (link "Ver catálogo" en el panel).
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),

    // Al guardar cambios en el panel se hace un POST aquí para que Next.js
    // invalide su caché (`revalidateTag('catalogo')`). Vacío = no se avisa.
    'revalidate_url' => env('NEXT_REVALIDATE_URL', env('FRONTEND_URL', 'http://localhost:3000').'/api/revalidate'),
    'revalidate_secret' => env('NEXT_REVALIDATE_SECRET'),

    // Ancho máximo al que se redimensionan las fotos subidas.
    'imagen_ancho_max' => 1400,

    // Ancho máximo del logotipo de la cabecera (se muestra a ~360px, 2x para retina).
    'logo_ancho_max' => 720,

];
