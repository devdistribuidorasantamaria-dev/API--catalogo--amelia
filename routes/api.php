<?php

use App\Http\Controllers\Api\AnaliticaApiController;
use App\Http\Controllers\Api\CatalogoApiController;
use Illuminate\Support\Facades\Route;

// API pública de sólo lectura: la consume el frontend Next.js.
// Toda la escritura del catálogo vive en el panel admin (rutas web con sesión).
Route::get('/catalogo', [CatalogoApiController::class, 'index']);
Route::get('/prendas/{prenda:slug}', [CatalogoApiController::class, 'prenda']);

// Única excepción a lo anterior: ingesta de analítica. No lee nada, no devuelve
// datos (siempre 204) y guarda sólo tipo + prenda + marca de tiempo. El límite
// va explícito porque bootstrap/app.php no aplica el grupo throttle:api.
Route::post('/eventos', [AnaliticaApiController::class, 'store'])
    ->middleware('throttle:60,1');
