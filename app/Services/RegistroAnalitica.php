<?php

namespace App\Services;

use App\Enums\TipoEvento;
use App\Models\EventoAnalitica;
use Illuminate\Http\Request;

/**
 * Registro de eventos del catálogo público.
 *
 * No guarda ningún dato personal: ni IP, ni user-agent, ni cookie, ni referente.
 * Lo único que sale de la petición es una huella con sal diaria que sirve para
 * distinguir visitas únicas de recargas, y que caduca sola cada medianoche.
 */
class RegistroAnalitica
{
    /**
     * Agentes que no son visitantes. Filtro grueso a propósito: buscadores,
     * previsualizadores de enlaces, navegadores automatizados y clientes de
     * consola. Los que podrían confundirse con un navegador de verdad se
     * exigen con la barra del producto (whatsapp/, java/) para no descartar a
     * quien abre el catálogo desde el navegador embebido de esas apps.
     */
    public const PATRON_BOT = '/bot\b|bot\/|crawl|spider|slurp|scrap|archiver|preview|headless|'
        .'phantomjs|selenium|puppeteer|playwright|lighthouse|pagespeed|'
        .'curl\/|wget|python-requests|python-urllib|okhttp|libwww|httpclient|java\/|go-http|'
        .'facebookexternalhit|whatsapp\/|telegrambot|discordbot|embedly|feedfetcher|'
        .'mediapartners|semrush|ahrefs|petalbot|yandex|baiduspider|bingpreview/i';

    public function esBot(?string $userAgent): bool
    {
        // Un navegador real siempre manda user-agent; su ausencia delata un script.
        if (blank($userAgent)) {
            return true;
        }

        return preg_match(self::PATRON_BOT, $userAgent) === 1;
    }

    /**
     * Huella irreversible del visitante: HMAC-SHA256 de ip+user-agent+fecha con
     * la APP_KEY como clave. Cambia sola cada día, así que ni permite volver a
     * la IP ni correlacionar al mismo visitante entre dos días distintos.
     */
    public function huella(Request $request): string
    {
        return hash_hmac(
            'sha256',
            $request->ip().'|'.$request->userAgent().'|'.now()->toDateString(),
            (string) config('app.key'),
        );
    }

    /**
     * Guarda el evento. Devuelve false si se descartó por venir de un bot;
     * el controlador responde igual en los dos casos.
     */
    public function registrar(TipoEvento $tipo, ?int $prendaId, Request $request): bool
    {
        if ($this->esBot($request->userAgent())) {
            return false;
        }

        EventoAnalitica::create([
            'tipo' => $tipo,
            'prenda_id' => $prendaId,
            'visitante_hash' => $this->huella($request),
            'creado_en' => now(),
        ]);

        return true;
    }
}
