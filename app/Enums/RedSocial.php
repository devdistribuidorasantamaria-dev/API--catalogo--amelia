<?php

namespace App\Enums;

/**
 * Redes sociales de la boutique. El logotipo de cada una ya vive en el
 * frontend: aquí sólo se guarda la dirección, y la red aparece en el catálogo
 * en cuanto esa dirección tiene algo.
 *
 * El orden de los casos es el orden en que se muestran en el pie y en el panel.
 */
enum RedSocial: string
{
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case Tiktok = 'tiktok';

    /** Clave con la que se guarda en la tabla `ajustes`. */
    public function clave(): string
    {
        return 'red_'.$this->value;
    }

    public function rotulo(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook',
            self::Instagram => 'Instagram',
            self::Tiktok => 'TikTok',
        };
    }

    /** Dominio con el que se arma la dirección cuando sólo se escribe el usuario. */
    public function dominio(): string
    {
        return match ($this) {
            self::Facebook => 'facebook.com',
            self::Instagram => 'instagram.com',
            self::Tiktok => 'tiktok.com',
        };
    }

    public function ejemplo(): string
    {
        return match ($this) {
            self::Facebook => 'https://facebook.com/ameliaboutique',
            self::Instagram => 'https://instagram.com/ameliaboutique',
            self::Tiktok => 'https://tiktok.com/@ameliaboutique',
        };
    }

    /**
     * Dirección completa a partir de lo que se escriba en el panel: acepta la
     * URL entera, el dominio sin `https://` o sólo el usuario (`@amelia`).
     *
     * Devuelve null si no queda nada que guardar.
     */
    public function normalizar(?string $valor): ?string
    {
        $texto = trim((string) $valor);

        if ($texto === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $texto) === 1) {
            return $texto;
        }

        // Pegaron el dominio sin protocolo: «instagram.com/amelia».
        if (str_contains($texto, '/') || str_contains($texto, '.')) {
            return 'https://'.ltrim($texto, '/');
        }

        // Sólo el usuario. TikTok lo lleva con arroba en la ruta; las otras no.
        $usuario = ltrim($texto, '@');

        return 'https://'.$this->dominio().'/'.($this === self::Tiktok ? '@' : '').$usuario;
    }
}
