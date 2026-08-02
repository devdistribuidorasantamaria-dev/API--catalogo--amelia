<?php

use App\Http\Controllers\Api\CatalogoApiController;
use Illuminate\Support\Facades\Route;

// API pública de sólo lectura: la consume el frontend Next.js.
// Toda la escritura vive en el panel admin (rutas web con sesión).
Route::get('/catalogo', [CatalogoApiController::class, 'index']);
Route::get('/prendas/{prenda:slug}', [CatalogoApiController::class, 'prenda']);
