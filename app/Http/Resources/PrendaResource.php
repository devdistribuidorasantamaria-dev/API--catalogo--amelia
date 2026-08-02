<?php

namespace App\Http\Resources;

use App\Models\Prenda;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Prenda */
class PrendaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'slug' => $this->slug,
            'descripcion' => $this->descripcion,
            'tallas' => $this->tallas ?? [],
            'precio_desde' => $this->precio_desde,
            'precio_hasta' => $this->precio_hasta,
            // Cadena ya formateada para que el frontend no duplique la lógica de rangos.
            'precio_texto' => $this->precioTexto(),
            'imagenes' => $this->whenLoaded(
                'imagenes',
                fn () => $this->imagenes->map(fn ($img) => $img->url())->values(),
                []
            ),
        ];
    }
}
