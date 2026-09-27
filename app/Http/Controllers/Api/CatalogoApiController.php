<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PrendaResource;
use App\Models\Ajuste;
use App\Models\Prenda;
use App\Models\Seccion;
use App\Services\ContactoWhatsapp;
use App\Services\Logotipo;
use App\Services\RedesSociales;
use Illuminate\Http\JsonResponse;

class CatalogoApiController extends Controller
{
    public function __construct(
        private readonly ContactoWhatsapp $whatsapp,
        private readonly Logotipo $logotipo,
        private readonly RedesSociales $redes,
    ) {}

    /**
     * Catálogo completo agrupado en bloques, en el orden en que se renderiza.
     * El primer bloque puede tener seccion=null (prendas sin sección, sin encabezado).
     */
    public function index(): JsonResponse
    {
        $prendas = Prenda::query()
            ->activas()
            ->ordenadas()
            ->with('imagenes')
            ->get();

        $secciones = Seccion::query()->ordenadas()->get();

        $bloques = [];

        // Bloque sin encabezado primero, igual que en el maquetado original.
        $sueltas = $prendas->whereNull('seccion_id');
        if ($sueltas->isNotEmpty()) {
            $bloques[] = [
                'seccion' => null,
                'prendas' => PrendaResource::collection($sueltas->values())->resolve(),
            ];
        }

        foreach ($secciones as $seccion) {
            $delBloque = $prendas->where('seccion_id', $seccion->id);

            if ($delBloque->isEmpty()) {
                continue;
            }

            $bloques[] = [
                'seccion' => [
                    'id' => $seccion->id,
                    'nombre' => $seccion->nombre,
                    'slug' => $seccion->slug,
                ],
                'prendas' => PrendaResource::collection($delBloque->values())->resolve(),
            ];
        }

        return response()->json([
            'subtitulo' => Ajuste::obtener('subtitulo', 'Colección · Santo Domingo, Ecuador'),
            // null = no se subió logotipo; el frontend escribe el nombre en Cormorant.
            'logo_url' => $this->logotipo->url(),
            'logo_ancho' => $this->logotipo->ancho(),
            'logo_alto' => $this->logotipo->alto(),
            // null cuando no hay número configurado: el frontend esconde el botón.
            'whatsapp_url' => $this->whatsapp->url(),
            // El número suelto permite al frontend armar mensajes propios
            // (consultar una prenda, consultar el carrito).
            'whatsapp_numero' => $this->whatsapp->numero(),
            // Sólo las redes con dirección guardada: el pie del catálogo pinta
            // el logotipo de cada una de esta lista y nada más.
            'redes' => $this->redes->lista(),
            'total_prendas' => $prendas->count(),
            'bloques' => $bloques,
        ]);
    }

    public function prenda(Prenda $prenda): JsonResponse
    {
        abort_unless($prenda->activa, 404);

        $prenda->load('imagenes', 'seccion');

        return response()->json([
            'data' => (new PrendaResource($prenda))->resolve(),
            'seccion' => $prenda->seccion ? [
                'id' => $prenda->seccion->id,
                'nombre' => $prenda->seccion->nombre,
                'slug' => $prenda->seccion->slug,
            ] : null,
        ]);
    }
}
