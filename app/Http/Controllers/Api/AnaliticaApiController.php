<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegistrarEventoRequest;
use App\Services\RegistroAnalitica;
use Illuminate\Http\Response;

class AnaliticaApiController extends Controller
{
    public function __construct(
        private readonly RegistroAnalitica $analitica,
    ) {}

    /**
     * Única escritura de la API. No devuelve datos ni lee nada del catálogo.
     *
     * Responde 204 tanto si el evento se guardó como si se descartó por bot:
     * el frontend dispara y se olvida, y el criterio del filtro no se filtra
     * hacia fuera.
     */
    public function store(RegistrarEventoRequest $request): Response
    {
        $this->analitica->registrar($request->tipo(), $request->prendaId(), $request);

        return response()->noContent();
    }
}
