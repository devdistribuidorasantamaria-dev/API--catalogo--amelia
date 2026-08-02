<?php

namespace Database\Seeders;

use App\Models\Ajuste;
use App\Models\Prenda;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@ameliaboutique.com'],
            [
                'name' => 'Amelia Boutique',
                'password' => Hash::make('Amelia@2026'),
                'email_verified_at' => now(),
            ]
        );

        Ajuste::guardar('subtitulo', 'Colección · Santo Domingo, Ecuador');

        $secciones = collect([
            ['nombre' => 'Vestidos', 'orden' => 1],
            ['nombre' => 'Blusas', 'orden' => 2],
            ['nombre' => 'Ropa deportiva', 'orden' => 3],
        ])->mapWithKeys(function (array $datos) {
            $seccion = Seccion::firstOrCreate(
                ['nombre' => $datos['nombre']],
                ['orden' => $datos['orden'], 'slug' => Seccion::slugUnico($datos['nombre'])]
            );

            return [$datos['nombre'] => $seccion];
        });

        $demo = [
            ['Vestido midi plisado', 'Vestidos', 'Gasa plisada con forro interno y cierre invisible.', ['XS', 'S', 'M', 'L'], 42.00, null, 1, 3],
            ['Vestido cruzado lino', 'Vestidos', 'Lino lavado, escote cruzado y cinto del mismo tejido.', ['S', 'M', 'L'], 38.00, 45.00, 2, 1],
            ['Blusa manga globo', 'Blusas', 'Popelina de algodón, puños elásticos.', ['S', 'M', 'L', 'XL'], 24.50, null, 1, 2],
            ['Blusa satinada cuello alto', 'Blusas', null, ['36', '38', '40'], 27.00, null, 2, 0],
            ['Conjunto deportivo alto impacto', 'Ropa deportiva', 'Tejido compresivo con secado rápido.', ['XS', 'S', 'M'], 35.00, 40.00, 1, 0],
            ['Pañuelo de seda', null, 'Seda 100 %, bordes rolados a mano.', [], 15.00, null, 1, 1],
        ];

        foreach ($demo as [$nombre, $seccion, $descripcion, $tallas, $desde, $hasta, $orden, $numFotos]) {
            $prenda = Prenda::firstOrCreate(
                ['slug' => Prenda::slugUnico($nombre)],
                [
                    'nombre' => $nombre,
                    'seccion_id' => $seccion ? $secciones[$seccion]->id : null,
                    'descripcion' => $descripcion,
                    'tallas' => $tallas,
                    'precio_desde' => $desde,
                    'precio_hasta' => $hasta,
                    'activa' => true,
                    'orden' => $orden,
                ]
            );

            // Fotos de relleno para poder probar la portada y el carrusel sin
            // tener que subir imágenes reales. Se reemplazan desde el panel.
            if ($numFotos > 0 && $prenda->imagenes()->doesntExist()) {
                for ($i = 0; $i < $numFotos; $i++) {
                    $prenda->imagenes()->create([
                        'ruta' => $this->fotoDeRelleno($prenda->nombre, $i),
                        'orden' => $i,
                    ]);
                }
            }
        }
    }

    /**
     * Genera un JPEG plano con el nombre de la prenda y lo guarda en el disco público.
     */
    private function fotoDeRelleno(string $texto, int $indice): string
    {
        [$ancho, $alto] = [600, 800];
        $lienzo = imagecreatetruecolor($ancho, $alto);

        $tono = 32 + ($indice * 12);
        imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, $tono, $tono, $tono));

        $tinta = imagecolorallocate($lienzo, 150, 150, 150);
        imagerectangle($lienzo, 20, 20, $ancho - 21, $alto - 21, imagecolorallocate($lienzo, $tono + 25, $tono + 25, $tono + 25));

        $etiqueta = 'AMELIA';
        imagestring($lienzo, 5, (int) (($ancho - strlen($etiqueta) * 9) / 2), (int) ($alto / 2) - 20, $etiqueta, $tinta);

        // imagestring sólo escribe Latin-1: hay que transliterar la ñ y los acentos.
        $sub = mb_substr(Str::ascii(mb_strtoupper($texto)), 0, 26);
        imagestring($lienzo, 3, (int) (($ancho - strlen($sub) * 7) / 2), (int) ($alto / 2) + 6, $sub, $tinta);

        ob_start();
        imagejpeg($lienzo, null, 85);
        $binario = ob_get_clean();
        imagedestroy($lienzo);

        $ruta = 'prendas/demo-'.md5($texto.$indice).'.jpg';
        Storage::disk('public')->put($ruta, $binario);

        return $ruta;
    }
}
