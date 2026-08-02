<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImagenService
{
    /**
     * Redimensiona la foto a un ancho máximo, la reencoda como JPEG y la guarda
     * en el disco 'public'. Devuelve la ruta relativa (ej. prendas/ab12cd.jpg).
     *
     * Se reencoda siempre para normalizar formatos (HEIC/PNG/WEBP desde el móvil)
     * y para descartar metadatos EXIF del archivo original.
     */
    public function guardar(UploadedFile $archivo, string $carpeta = 'prendas'): string
    {
        $anchoMax = (int) config('amelia.imagen_ancho_max', 1400);
        $origen = $this->cargar($archivo);

        [$ancho, $alto] = [imagesx($origen), imagesy($origen)];
        $escala = min(1, $anchoMax / max(1, $ancho));
        $nuevoAncho = max(1, (int) round($ancho * $escala));
        $nuevoAlto = max(1, (int) round($alto * $escala));

        $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
        // Fondo blanco: al pasar PNG/WEBP con transparencia a JPEG el alfa se pierde
        // y sin esto quedaría negro.
        imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255));
        imagecopyresampled($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
        imagedestroy($origen);

        $ruta = $carpeta.'/'.Str::random(24).'.jpg';

        ob_start();
        imagejpeg($destino, null, 82);
        $binario = ob_get_clean();
        imagedestroy($destino);

        Storage::disk('public')->put($ruta, $binario);

        return $ruta;
    }

    public function eliminar(?string $ruta): void
    {
        if (filled($ruta)) {
            Storage::disk('public')->delete($ruta);
        }
    }

    private function cargar(UploadedFile $archivo): \GdImage
    {
        $imagen = @imagecreatefromstring((string) file_get_contents($archivo->getRealPath()));

        if ($imagen === false) {
            throw new RuntimeException('No se pudo leer la imagen: '.$archivo->getClientOriginalName());
        }

        // Respeta la orientación EXIF de las fotos de celular.
        if (function_exists('exif_read_data') && in_array($archivo->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            $exif = @exif_read_data($archivo->getRealPath());
            $rotacion = match ($exif['Orientation'] ?? 1) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };

            if ($rotacion !== 0) {
                $rotada = imagerotate($imagen, $rotacion, 0);
                if ($rotada !== false) {
                    imagedestroy($imagen);
                    $imagen = $rotada;
                }
            }
        }

        return $imagen;
    }
}
