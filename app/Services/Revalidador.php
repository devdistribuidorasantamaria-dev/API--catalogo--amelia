<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class Revalidador
{
    /**
     * Avisa al frontend Next.js que el catálogo cambió para que purgue su caché.
     * Nunca lanza: si el frontend está caído el guardado en el panel debe funcionar igual.
     */
    public function avisar(): void
    {
        $url = config('amelia.revalidate_url');
        $secreto = config('amelia.revalidate_secret');

        if (blank($url) || blank($secreto)) {
            return;
        }

        try {
            Http::timeout(3)
                ->withHeaders(['X-Revalidate-Secret' => $secreto])
                ->post($url, ['tag' => 'catalogo'])
                ->throw();
        } catch (Throwable $e) {
            Log::warning('No se pudo revalidar el frontend: '.$e->getMessage());
        }
    }
}
