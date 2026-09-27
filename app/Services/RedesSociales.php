<?php

namespace App\Services;

use App\Enums\RedSocial;
use App\Models\Ajuste;

/**
 * Direcciones de las redes sociales, editables desde el panel (Ajustes).
 *
 * Las redes son fijas —las tres que usa la boutique— y sus logotipos viven en
 * el frontend: aquí sólo viaja la dirección. Una red sin dirección guardada no
 * sale en la API, así que tampoco aparece en el pie del catálogo.
 */
class RedesSociales
{
    /**
     * Lo guardado para cada red, con null en las que no tienen dirección.
     *
     * @return array<string, string|null>
     */
    public function todas(): array
    {
        $valores = [];

        foreach (RedSocial::cases() as $red) {
            $valor = Ajuste::obtener($red->clave());
            $valores[$red->value] = filled($valor) ? $valor : null;
        }

        return $valores;
    }

    public function url(RedSocial $red): ?string
    {
        return $this->todas()[$red->value];
    }

    /**
     * Las redes que sí tienen dirección, en el orden del enum: es lo que
     * consume el frontend para pintar el pie.
     *
     * @return array<int, array{red: string, nombre: string, url: string}>
     */
    public function lista(): array
    {
        $lista = [];

        foreach (RedSocial::cases() as $red) {
            $url = $this->url($red);

            if ($url === null) {
                continue;
            }

            $lista[] = [
                'red' => $red->value,
                'nombre' => $red->rotulo(),
                'url' => $url,
            ];
        }

        return $lista;
    }

    /**
     * Guarda las direcciones ya normalizadas.
     *
     * @param  array<string, string|null>  $valores  indexado por el valor del enum
     */
    public function guardar(array $valores): void
    {
        foreach (RedSocial::cases() as $red) {
            Ajuste::guardar($red->clave(), $red->normalizar($valores[$red->value] ?? null));
        }
    }
}
