<?php

namespace App\Services;

use App\Models\Ajuste;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Logotipo de la cabecera del catálogo. Se sube desde el panel (Ajustes) y viaja
 * al frontend por la API. Sin archivo guardado, el catálogo escribe el nombre
 * «Amelia · Boutique» en Cormorant, como respaldo.
 *
 * Las dimensiones se guardan junto a la ruta porque `next/image` necesita el
 * tamaño real para reservar el espacio sin salto de maquetado.
 */
class Logotipo
{
    public function __construct(private readonly ImagenService $imagenes) {}

    public function ruta(): ?string
    {
        return Ajuste::obtener('logo_ruta');
    }

    public function url(): ?string
    {
        $ruta = $this->ruta();

        return $ruta ? Storage::disk('public')->url($ruta) : null;
    }

    public function ancho(): ?int
    {
        return $this->entero('logo_ancho');
    }

    public function alto(): ?int
    {
        return $this->entero('logo_alto');
    }

    /** Sube el nuevo archivo y recién entonces borra el anterior. */
    public function reemplazar(UploadedFile $archivo): void
    {
        $anterior = $this->ruta();
        $datos = $this->imagenes->guardarLogo($archivo);

        Ajuste::guardar('logo_ruta', $datos['ruta']);
        Ajuste::guardar('logo_ancho', (string) $datos['ancho']);
        Ajuste::guardar('logo_alto', (string) $datos['alto']);

        $this->imagenes->eliminar($anterior);
    }

    public function eliminar(): void
    {
        $this->imagenes->eliminar($this->ruta());

        Ajuste::guardar('logo_ruta', null);
        Ajuste::guardar('logo_ancho', null);
        Ajuste::guardar('logo_alto', null);
    }

    private function entero(string $clave): ?int
    {
        $valor = Ajuste::obtener($clave);

        return filled($valor) ? (int) $valor : null;
    }
}
