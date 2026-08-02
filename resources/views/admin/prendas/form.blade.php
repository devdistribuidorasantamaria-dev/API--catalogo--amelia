@php
    $editando = $prenda->exists;
    $imagenesActuales = $prenda->exists
        ? $prenda->imagenes->map(fn ($img) => ['id' => $img->id, 'url' => $img->url()])->values()
        : collect();
@endphp

<x-app-layout :title="$editando ? 'Editar prenda' : 'Nueva prenda'"
              eyebrow="Catálogo"
              :heading="$editando ? $prenda->nombre : 'Nueva prenda'">
    <x-slot name="actions">
        <a href="{{ route('admin.prendas.index') }}" class="a-btn a-btn-ghost">← Volver</a>
    </x-slot>

    <form method="POST"
          action="{{ $editando ? route('admin.prendas.update', $prenda) : route('admin.prendas.store') }}"
          enctype="multipart/form-data"
          x-data="gestorFotos(@js($imagenesActuales))"
          class="grid gap-10 lg:grid-cols-[1fr_380px]">
        @csrf
        @if ($editando)
            @method('PUT')
        @endif

        {{-- Datos de la prenda --}}
        <div class="space-y-6">
            <div>
                <x-input-label for="nombre" value="Nombre de la prenda" />
                <x-text-input id="nombre" name="nombre" :value="old('nombre', $prenda->nombre)"
                              placeholder="Vestido midi plisado" required autofocus autocomplete="off" />
                <x-input-error :messages="$errors->get('nombre')" />
            </div>

            <div>
                <x-input-label for="descripcion" value="Descripción (opcional)" />
                <textarea id="descripcion" name="descripcion" rows="3" class="a-input"
                          placeholder="Detalle de tela, corte o composición…">{{ old('descripcion', $prenda->descripcion) }}</textarea>
                <x-input-error :messages="$errors->get('descripcion')" />
            </div>

            <div>
                <x-input-label for="tallas" value="Tallas" />
                <x-text-input id="tallas" name="tallas"
                              :value="old('tallas', implode(', ', $prenda->tallas ?? []))"
                              placeholder="XS, S, M, L, XL" autocomplete="off" />
                <p class="a-hint">Sepáralas con comas. Sirven letras o números (36, 38, 40).</p>
                <x-input-error :messages="$errors->get('tallas')" />
            </div>

            <div>
                <x-input-label for="seccion_id" value="Sección" />
                <select id="seccion_id" name="seccion_id" class="a-input">
                    <option value="">— Sin sección —</option>
                    @foreach ($secciones as $seccion)
                        <option value="{{ $seccion->id }}" @selected((int) old('seccion_id', $prenda->seccion_id) === $seccion->id)>
                            {{ $seccion->nombre }}
                        </option>
                    @endforeach
                </select>
                <p class="a-hint">
                    Agrupa las prendas por sección. Las secciones se crean en
                    <a href="{{ route('admin.secciones.index') }}" class="underline hover:text-ink">Secciones</a>.
                </p>
                <x-input-error :messages="$errors->get('seccion_id')" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="precio_desde" value="Precio" />
                    <x-text-input id="precio_desde" name="precio_desde" type="number" step="0.01" min="0"
                                  :value="old('precio_desde', $prenda->precio_desde)" placeholder="25.00" />
                    <x-input-error :messages="$errors->get('precio_desde')" />
                </div>
                <div>
                    <x-input-label for="precio_hasta" value="Hasta (opcional)" />
                    <x-text-input id="precio_hasta" name="precio_hasta" type="number" step="0.01" min="0"
                                  :value="old('precio_hasta', $prenda->precio_hasta)" placeholder="30.00" />
                    <x-input-error :messages="$errors->get('precio_hasta')" />
                </div>
            </div>
            <p class="a-hint -mt-4">Llena sólo «Precio» para un valor único, o ambos para mostrar un rango (25.00 – 30.00).</p>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="orden" value="Orden dentro de la sección" />
                    <x-text-input id="orden" name="orden" type="number" min="0"
                                  :value="old('orden', $prenda->orden ?? 0)" />
                    <p class="a-hint">Menor número aparece primero.</p>
                    <x-input-error :messages="$errors->get('orden')" />
                </div>
                <div class="flex items-end">
                    <label for="activa" class="inline-flex items-center gap-3 pb-3">
                        <input type="hidden" name="activa" value="0">
                        <input id="activa" type="checkbox" name="activa" value="1"
                               @checked(old('activa', $prenda->activa ?? true))
                               class="a-check">
                        <span class="text-[11px] uppercase tracking-label">Publicada en el catálogo</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Fotos --}}
        <div class="space-y-4">
            <div>
                <x-input-label value="Fotos" />
                <p class="a-hint !mt-0 mb-3">La primera es la portada. Arrastra con ↑ ↓ para reordenar.</p>
            </div>

            {{-- Imágenes ya guardadas --}}
            <template x-for="(img, i) in imagenes" :key="img.id">
                <div class="flex items-center gap-3 border border-line bg-panel p-2">
                    <div class="relative h-24 w-[72px] shrink-0 overflow-hidden border border-line">
                        <img :src="img.url" alt="" class="h-full w-full object-cover">
                        <span x-show="i === 0"
                              class="absolute inset-x-0 bottom-0 bg-ink py-0.5 text-center text-[8px] uppercase tracking-label text-paper">
                            Portada
                        </span>
                    </div>
                    <div class="flex flex-1 flex-col gap-1.5">
                        <span class="text-[10px] uppercase tracking-label text-muted" x-text="'Foto ' + (i + 1)"></span>
                        <div class="flex gap-1.5">
                            <button type="button" class="a-btn a-btn-ghost !px-2.5 !py-1" @click="subir(i)" :disabled="i === 0" title="Subir">↑</button>
                            <button type="button" class="a-btn a-btn-ghost !px-2.5 !py-1" @click="bajar(i)" :disabled="i === imagenes.length - 1" title="Bajar">↓</button>
                            <button type="button" class="a-btn a-btn-danger !px-2.5 !py-1" @click="quitar(i)" title="Quitar">✕</button>
                        </div>
                    </div>
                    <input type="hidden" name="orden_imagenes[]" :value="img.id">
                </div>
            </template>

            {{-- IDs a eliminar --}}
            <template x-for="id in eliminadas" :key="'del-' + id">
                <input type="hidden" name="eliminar_imagenes[]" :value="id">
            </template>

            {{-- Nuevas fotos pendientes de subir --}}
            <template x-for="(nueva, i) in nuevas" :key="nueva.key">
                <div class="flex items-center gap-3 border border-dashed border-line bg-panel p-2">
                    <div class="h-24 w-[72px] shrink-0 overflow-hidden border border-line">
                        <img :src="nueva.preview" alt="" class="h-full w-full object-cover">
                    </div>
                    <div class="flex-1">
                        <span class="a-eyebrow">Por subir</span>
                        <p class="mt-1 truncate text-[11px] text-muted" x-text="nueva.nombre"></p>
                    </div>
                    <button type="button" class="a-btn a-btn-danger !px-2.5 !py-1" @click="quitarNueva(i)" title="Quitar">✕</button>
                </div>
            </template>

            <button type="button" @click="$refs.archivo.click()"
                    class="flex h-24 w-full items-center justify-center gap-2 border border-dashed border-line bg-panel text-[11px] uppercase tracking-label text-[#5a5a5a] transition-colors hover:border-ink hover:text-ink">
                + Agregar fotos
            </button>
            <input x-ref="archivo" type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple
                   class="hidden" @change="agregar($event)">

            <p class="a-hint">JPG, PNG o WEBP. Máximo 8 MB y 12 fotos por prenda. Se redimensionan a {{ config('amelia.imagen_ancho_max') }} px de ancho al guardar.</p>
            <x-input-error :messages="$errors->get('fotos')" />
            @foreach ($errors->get('fotos.*') as $mensajes)
                <x-input-error :messages="$mensajes" />
            @endforeach

            <div class="flex flex-wrap justify-end gap-2 border-t border-line pt-6">
                <a href="{{ route('admin.prendas.index') }}" class="a-btn a-btn-ghost">Cancelar</a>
                <x-primary-button>{{ $editando ? 'Guardar cambios' : 'Guardar prenda' }}</x-primary-button>
            </div>
        </div>
    </form>

    <script>
        function gestorFotos(iniciales) {
            return {
                imagenes: iniciales,
                eliminadas: [],
                nuevas: [],
                contador: 0,

                subir(i) {
                    if (i > 0) this.imagenes.splice(i - 1, 0, this.imagenes.splice(i, 1)[0]);
                },
                bajar(i) {
                    if (i < this.imagenes.length - 1) this.imagenes.splice(i + 1, 0, this.imagenes.splice(i, 1)[0]);
                },
                quitar(i) {
                    this.eliminadas.push(this.imagenes[i].id);
                    this.imagenes.splice(i, 1);
                },

                agregar(evento) {
                    for (const archivo of evento.target.files) {
                        this.nuevas.push({
                            key: 'n' + this.contador++,
                            nombre: archivo.name,
                            preview: URL.createObjectURL(archivo),
                            archivo,
                        });
                    }
                    // El input conserva su propio FileList; se reconstruye para
                    // que coincida con lo que se ve en la lista.
                    this.sincronizarInput(evento.target);
                },
                quitarNueva(i) {
                    URL.revokeObjectURL(this.nuevas[i].preview);
                    this.nuevas.splice(i, 1);
                    this.sincronizarInput(this.$refs.archivo);
                },
                sincronizarInput(input) {
                    const dt = new DataTransfer();
                    this.nuevas.forEach((n) => dt.items.add(n.archivo));
                    input.files = dt.files;
                },
            };
        }
    </script>
</x-app-layout>
