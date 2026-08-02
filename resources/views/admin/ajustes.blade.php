<x-app-layout title="Ajustes" eyebrow="Panel" heading="Ajustes">
    <div class="max-w-xl">
        <form method="POST" action="{{ route('admin.ajustes.update') }}" class="space-y-8">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="subtitulo" value="Subtítulo del catálogo" />
                <x-text-input id="subtitulo" name="subtitulo" :value="old('subtitulo', $subtitulo)"
                              placeholder="Colección · Santo Domingo, Ecuador" />
                <p class="a-hint">Línea que aparece bajo el logo, en versalitas espaciadas.</p>
                <x-input-error :messages="$errors->get('subtitulo')" />
            </div>

            <div class="space-y-5 border-t border-line pt-8">
                <div>
                    <span class="a-eyebrow">Botón de contacto</span>
                    <p class="a-hint !mt-2">
                        Aparece flotando en el catálogo y abre el chat de WhatsApp.
                        Deja el número vacío para esconderlo.
                    </p>
                </div>

                <div>
                    <x-input-label for="whatsapp_numero" value="Número de WhatsApp" />
                    <x-text-input id="whatsapp_numero" name="whatsapp_numero"
                                  :value="old('whatsapp_numero', $whatsappNumero)"
                                  placeholder="593987654321" inputmode="tel" autocomplete="off" />
                    <p class="a-hint">
                        Con código de país y sin el 0 inicial. Ecuador: <code class="text-ink">593</code> +
                        el celular sin el 0 (0987654321 → 593987654321).
                        Puedes escribirlo con espacios o «+», se limpia al guardar.
                    </p>
                    <x-input-error :messages="$errors->get('whatsapp_numero')" />
                </div>

                <div>
                    <x-input-label for="whatsapp_mensaje" value="Mensaje precargado" />
                    <textarea id="whatsapp_mensaje" name="whatsapp_mensaje" rows="3" class="a-input"
                              placeholder="{{ \App\Services\ContactoWhatsapp::MENSAJE_POR_DEFECTO }}">{{ old('whatsapp_mensaje', $whatsappMensaje) }}</textarea>
                    <p class="a-hint">Texto con el que se abre el chat. Si lo dejas vacío se usa el del ejemplo.</p>
                    <x-input-error :messages="$errors->get('whatsapp_mensaje')" />
                </div>

                @if ($whatsappUrl)
                    <p class="text-[11px] text-muted">
                        Probar el enlace guardado:
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
                           class="text-ink underline">abrir chat ↗</a>
                    </p>
                @endif
            </div>

            <div class="border-t border-line pt-6">
                <x-primary-button>Guardar ajustes</x-primary-button>
            </div>
        </form>

        <div class="mt-12 border border-line bg-panel p-6">
            <span class="a-eyebrow">Caché del catálogo</span>
            <p class="mt-3 text-[13px] leading-relaxed text-muted">
                Cada cambio guardado aquí avisa al frontend
                (<code class="text-ink">{{ config('amelia.revalidate_url') ?: 'sin configurar' }}</code>)
                para que vuelva a leer la API. Si el aviso falla, el cambio se guarda igual y el
                catálogo se actualiza cuando expire su caché.
            </p>
        </div>
    </div>
</x-app-layout>
