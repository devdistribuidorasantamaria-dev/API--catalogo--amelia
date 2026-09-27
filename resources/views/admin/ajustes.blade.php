<x-app-layout title="Ajustes" eyebrow="Panel" heading="Ajustes">
    <div class="max-w-xl">
        <form method="POST" action="{{ route('admin.ajustes.update') }}" class="space-y-8"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div>
                    <x-input-label for="logo" value="Logotipo de la cabecera" />
                    <p class="a-hint !mt-1">
                        PNG con fondo transparente o negro, apaisado (el catálogo lo muestra a
                        360&nbsp;px de ancho). Sin logotipo se escribe «Amelia · Boutique» en Cormorant.
                    </p>
                </div>

                @if ($logoUrl)
                    <div class="flex flex-wrap items-center gap-5 border border-line bg-black p-5">
                        <img src="{{ $logoUrl }}" alt="Logotipo actual" class="h-16 w-auto" />
                        <label class="flex items-center gap-2 text-[12px] text-muted">
                            <input type="checkbox" name="eliminar_logo" value="1"
                                   class="border-line bg-panel text-ink focus:ring-0" />
                            Quitar el logotipo y volver al nombre en texto
                        </label>
                    </div>
                @endif

                <input id="logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp"
                       class="a-input" />
                <x-input-error :messages="$errors->get('logo')" />
                <x-input-error :messages="$errors->get('eliminar_logo')" />
            </div>

            <div class="border-t border-line pt-8">
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


            <div class="space-y-5 border-t border-line pt-8">
                <div>
                    <span class="a-eyebrow">Redes sociales</span>
                    <p class="a-hint !mt-2">
                        Aparecen en el pie del catálogo, con el logotipo de cada red.
                        La que dejes vacía no se muestra. Puedes pegar el enlace completo
                        del perfil, el dominio («instagram.com/ameliaboutique») o sólo el
                        usuario («&#64;ameliaboutique»): se completa al guardar.
                    </p>
                </div>

                @foreach ($redes as $red)
                    <div>
                        <x-input-label :for="'red_'.$red->value" :value="$red->rotulo()" />
                        <x-text-input :id="'red_'.$red->value" :name="'redes['.$red->value.']'"
                                      :value="old('redes.'.$red->value, $redesGuardadas[$red->value])"
                                      :placeholder="$red->ejemplo()" inputmode="url"
                                      autocomplete="off" autocapitalize="off" spellcheck="false" />
                        @if ($redesGuardadas[$red->value])
                            <p class="a-hint">
                                Guardado:
                                <a href="{{ $redesGuardadas[$red->value] }}" target="_blank" rel="noopener"
                                   class="text-ink underline">abrir perfil &#8599;</a>
                            </p>
                        @endif
                        <x-input-error :messages="$errors->get('redes.'.$red->value)" />
                    </div>
                @endforeach
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
