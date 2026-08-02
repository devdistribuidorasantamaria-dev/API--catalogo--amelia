<x-app-layout title="Prendas" eyebrow="Catálogo" heading="Prendas">
    <x-slot name="actions">
        <a href="{{ route('admin.prendas.create') }}" class="a-btn a-btn-solid">+ Agregar prenda</a>
    </x-slot>

    @if ($prendas->isEmpty())
        <div class="border border-line px-6 py-20 text-center">
            <p class="font-serif text-2xl italic">Aún no hay prendas</p>
            <p class="mt-2 text-[13px] text-muted">Comienza el catálogo agregando la primera pieza.</p>
            <a href="{{ route('admin.prendas.create') }}" class="a-btn a-btn-solid mt-6">+ Agregar prenda</a>
        </div>
    @else
        <div class="overflow-x-auto border border-line">
            <table class="w-full min-w-[720px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-line text-[10px] uppercase tracking-label text-muted">
                        <th class="px-4 py-3 font-normal">Foto</th>
                        <th class="px-4 py-3 font-normal">Prenda</th>
                        <th class="px-4 py-3 font-normal">Sección</th>
                        <th class="px-4 py-3 font-normal">Tallas</th>
                        <th class="px-4 py-3 font-normal">Precio</th>
                        <th class="px-4 py-3 font-normal">Estado</th>
                        <th class="px-4 py-3 text-right font-normal">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($prendas as $prenda)
                        <tr class="align-middle">
                            <td class="px-4 py-3">
                                <div class="flex h-16 w-12 items-center justify-center overflow-hidden border border-line bg-panel">
                                    @if ($prenda->portada)
                                        <img src="{{ $prenda->portada->url() }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        <span class="font-serif text-[9px] italic tracking-widest text-[#3a3a3a]">AMELIA</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.prendas.edit', $prenda) }}" class="font-serif text-lg hover:underline">
                                    {{ $prenda->nombre }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-[11px] uppercase tracking-label text-muted">
                                {{ $prenda->seccion?->nombre ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($prenda->tallas ?? [] as $talla)
                                        <span class="border border-[#3a3a3a] px-2 py-0.5 text-[9px] uppercase tracking-label">{{ $talla }}</span>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-3 font-serif text-lg">{{ $prenda->precioTexto() }}</td>
                            <td class="px-4 py-3 text-[10px] uppercase tracking-label">
                                <span class="{{ $prenda->activa ? 'text-ink' : 'text-muted' }}">
                                    {{ $prenda->activa ? 'Publicada' : 'Oculta' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.prendas.edit', $prenda) }}" class="a-btn a-btn-ghost !px-3 !py-1.5">Editar</a>
                                    <form method="POST" action="{{ route('admin.prendas.destroy', $prenda) }}"
                                          onsubmit="return confirm('¿Eliminar «{{ addslashes($prenda->nombre) }}» del catálogo? También se borran sus fotos.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="a-btn a-btn-danger !px-3 !py-1.5">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-8">{{ $prendas->links() }}</div>
    @endif
</x-app-layout>
