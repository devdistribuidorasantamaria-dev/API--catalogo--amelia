<?php

namespace App\Enums;

/**
 * Los tres eventos que registra el catálogo.
 *
 * «Agregar» y «Consultar» se guardan por separado aunque el panel los sume como
 * «selecciones»: así se puede desglosar más adelante sin volver a instrumentar
 * el frontend ni perder el histórico.
 */
enum TipoEvento: string
{
    case Visita = 'visita';
    case Agregar = 'agregar';
    case Consultar = 'consultar';

    /** Los dos que llevan prenda: el interés por una pieza concreta. */
    public function esSeleccion(): bool
    {
        return $this !== self::Visita;
    }

    /**
     * Valores de los eventos de selección, para whereIn y validación.
     *
     * @return array<int, string>
     */
    public static function selecciones(): array
    {
        return [self::Agregar->value, self::Consultar->value];
    }

    public function rotulo(): string
    {
        return match ($this) {
            self::Visita => 'Visitas',
            self::Agregar => 'Agregar',
            self::Consultar => 'Consultar',
        };
    }
}
