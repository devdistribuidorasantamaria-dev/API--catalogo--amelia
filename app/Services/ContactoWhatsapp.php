<?php

namespace App\Services;

use App\Models\Ajuste;

/**
 * Datos del botón de contacto por WhatsApp, editables desde el panel.
 *
 * El número se guarda en formato internacional sin símbolos (ej. 593987654321):
 * es lo que espera wa.me. Si no hay número, el botón no se muestra.
 */
class ContactoWhatsapp
{
    public const MENSAJE_POR_DEFECTO = 'Hola Amelia Boutique, vi su catálogo y quisiera más información.';

    public function numero(): ?string
    {
        $numero = Ajuste::obtener('whatsapp_numero');

        return filled($numero) ? $numero : null;
    }

    public function mensaje(): string
    {
        $mensaje = Ajuste::obtener('whatsapp_mensaje');

        return filled($mensaje) ? $mensaje : self::MENSAJE_POR_DEFECTO;
    }

    public function url(): ?string
    {
        $numero = $this->numero();

        if ($numero === null) {
            return null;
        }

        return 'https://wa.me/'.$numero.'?text='.rawurlencode($this->mensaje());
    }

    /**
     * Deja sólo dígitos: acepta que en el panel se escriba "+593 98 765 4321".
     */
    public static function normalizarNumero(?string $numero): ?string
    {
        $limpio = preg_replace('/\D+/', '', (string) $numero);

        return $limpio === '' ? null : $limpio;
    }
}
