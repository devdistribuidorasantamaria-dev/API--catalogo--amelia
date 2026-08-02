<x-app-layout title="Secciones" eyebrow="Catálogo" heading="Secciones">
    <div class="grid gap-10 lg:grid-cols-[1fr_320px]">
        <div>
            @if ($secciones->isEmpty())
                <div class="border border-line px-6 py-16 text-center">
                    <p class="font-serif text-2xl italic">Sin secciones</p>
                    <p class="mt-2 text-[13px] text-muted">
                        Las prendas sin sección se muestran al inicio del catálogo, sin encabezado.
                    </p>
                </div>
            @else
                <ul class="divide-y divide-line border-y border-line">
                    @foreach ($secciones as $seccion)
                        <li class="py-4">
                            <form method="POST" action="{{ route('admin.secciones.update', $seccion) }}"
                                  class="flex flex-wrap items-end gap-3">
                                @csrf
                                @method('PUT')

                                <div class="min-w-[200px] flex-1">
                                    <x-input-label :for="'nombre-'.$seccion->id" value="Nombre" />
                                    <x-text-input :id="'nombre-'.$seccion->id" name="nombre" :value="$seccion->nombre" required />
                                </div>

                                <div class="w-24">
                                    <x-input-label :for="'orden-'.$seccion->id" value="Orden" />
                                    <x-text-input :id="'orden-'.$seccion->id" name="orden" type="number" min="0" :value="$seccion->orden" />
                                </div>

                                <div class="pb-1 text-[10px] uppercase tracking-label text-muted">
                                    {{ $seccion->prendas_count }} {{ Str::plural('prenda', $seccion->prendas_count) }}
                                </div>

                                <div class="flex gap-2 pb-0.5">
                                    <x-primary-button>Guardar</x-primary-button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('admin.secciones.destroy', $seccion) }}" class="mt-2"
                                  onsubmit="return confirm('¿Eliminar la sección «{{ addslashes($seccion->nombre) }}»? Sus {{ $seccion->prendas_count }} {{ Str::plural('prenda', $seccion->prendas_count) }} quedarán sin sección (no se borran).')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[10px] uppercase tracking-label text-muted hover:text-red-400">
                                    Eliminar sección
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="a-card h-fit p-6">
            <span class="a-eyebrow">Nueva sección</span>
            <h2 class="mt-2 font-serif text-2xl">Agregar</h2>

            <form method="POST" action="{{ route('admin.secciones.store') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <x-input-label for="nombre" value="Nombre" />
                    <x-text-input id="nombre" name="nombre" :value="old('nombre')" placeholder="Ropa deportiva" required />
                    <x-input-error :messages="$errors->get('nombre')" />
                </div>

                <div>
                    <x-input-label for="orden" value="Orden" />
                    <x-text-input id="orden" name="orden" type="number" min="0" :value="old('orden', 0)" />
                    <p class="a-hint">Menor número aparece primero en el catálogo.</p>
                    <x-input-error :messages="$errors->get('orden')" />
                </div>

                <x-primary-button class="w-full">Crear sección</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
