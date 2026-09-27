<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RedSocial;
use App\Http\Controllers\Controller;
use App\Models\Ajuste;
use App\Services\ContactoWhatsapp;
use App\Services\Logotipo;
use App\Services\RedesSociales;
use App\Services\Revalidador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AjusteController extends Controller
{
    public function __construct(
        private readonly Revalidador $revalidador,
        private readonly ContactoWhatsapp $whatsapp,
        private readonly Logotipo $logotipo,
        private readonly RedesSociales $redes,
    ) {}

    public function edit(): View
    {
        return view('admin.ajustes', [
            'subtitulo' => Ajuste::obtener('subtitulo', 'Colección · Santo Domingo, Ecuador'),
            'logoUrl' => $this->logotipo->url(),
            'whatsappNumero' => $this->whatsapp->numero(),
            'whatsappMensaje' => Ajuste::obtener('whatsapp_mensaje'),
            'whatsappUrl' => $this->whatsapp->url(),
            'redes' => RedSocial::cases(),
            'redesGuardadas' => $this->redes->todas(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // El número se limpia antes de validar para aceptar "+593 98 765 4321".
        $request->merge([
            'whatsapp_numero' => ContactoWhatsapp::normalizarNumero($request->input('whatsapp_numero')),
            // Las redes se normalizan antes de validar para aceptar tanto la URL
            // entera como «instagram.com/amelia» o sólo «@amelia».
            'redes' => collect(RedSocial::cases())
                ->mapWithKeys(fn (RedSocial $red) => [
                    $red->value => $red->normalizar($request->input('redes.'.$red->value)),
                ])
                ->all(),
        ]);

        $datos = $request->validate([
            'subtitulo' => ['nullable', 'string', 'max:160'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'eliminar_logo' => ['nullable', 'boolean'],
            // Rango de longitud de un número internacional según la E.164.
            'whatsapp_numero' => ['nullable', 'digits_between:8,15'],
            'whatsapp_mensaje' => ['nullable', 'string', 'max:300'],
            'redes' => ['array'],
            'redes.*' => ['nullable', 'url', 'max:255'],
        ], [
            'logo.image' => 'El logotipo debe ser una imagen PNG, JPG o WEBP.',
            'logo.max' => 'El logotipo no puede pesar más de 4 MB.',
            'whatsapp_numero.digits_between' => 'El número debe tener entre 8 y 15 dígitos, con código de país y sin el 0 inicial (ej. 593987654321).',
            'redes.*.url' => 'La dirección de :attribute no se entiende. Pega el enlace de tu perfil o escribe sólo el usuario.',
        ], collect(RedSocial::cases())
            ->mapWithKeys(fn (RedSocial $red) => ['redes.'.$red->value => $red->rotulo()])
            ->all());

        // Subir un archivo nuevo manda sobre la casilla de quitar.
        if ($request->hasFile('logo')) {
            $this->logotipo->reemplazar($request->file('logo'));
        } elseif ($request->boolean('eliminar_logo')) {
            $this->logotipo->eliminar();
        }

        Ajuste::guardar('subtitulo', $datos['subtitulo'] ?? null);
        Ajuste::guardar('whatsapp_numero', $datos['whatsapp_numero'] ?? null);
        Ajuste::guardar('whatsapp_mensaje', $datos['whatsapp_mensaje'] ?? null);
        $this->redes->guardar($datos['redes'] ?? []);

        $this->revalidador->avisar();

        return to_route('admin.ajustes.edit')->with('status', 'Ajustes guardados.');
    }
}
