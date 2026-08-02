<x-app-layout title="Resumen" eyebrow="Panel" heading="Resumen">
    <x-slot name="actions">
        <a href="{{ route('admin.prendas.create') }}" class="a-btn a-btn-solid">+ Agregar prenda</a>
    </x-slot>

    <div class="grid grid-cols-2 gap-px border border-line bg-line lg:grid-cols-4">
        @foreach ([
            ['Prendas', $totalPrendas],
            ['Publicadas', $prendasActivas],
            ['Secciones', $totalSecciones],
            ['Fotos', $totalFotos],
        ] as [$rotulo, $valor])
            <div class="bg-paper px-6 py-8">
                <span class="a-eyebrow">{{ $rotulo }}</span>
                <p class="mt-3 font-serif text-4xl font-medium leading-none">{{ $valor }}</p>
            </div>
        @endforeach
    </div>

    @if ($sinFoto > 0)
        <p class="mt-6 border border-line bg-panel px-4 py-3 text-[11px] uppercase tracking-label text-muted">
            {{ $sinFoto }} {{ Str::plural('prenda', $sinFoto) }} sin foto — se muestran con la marca de agua «AMELIA».
        </p>
    @endif

    <section class="mt-12">
        <span class="a-eyebrow">Últimas agregadas</span>

        @if ($recientes->isEmpty())
            <div class="mt-4 border border-line px-6 py-16 text-center">
                <p class="font-serif text-2xl italic">Aún no hay prendas</p>
                <p class="mt-2 text-[13px] text-muted">Comienza el catálogo agregando la primera pieza.</p>
                <a href="{{ route('admin.prendas.create') }}" class="a-btn a-btn-solid mt-6">+ Agregar prenda</a>
            </div>
        @else
            <ul class="mt-4 divide-y divide-line border-y border-line">
                @foreach ($recientes as $prenda)
                    <li class="flex flex-wrap items-baseline justify-between gap-3 py-4">
                        <div>
                            <a href="{{ route('admin.prendas.edit', $prenda) }}" class="font-serif text-xl hover:underline">
                                {{ $prenda->nombre }}
                            </a>
                            <span class="ml-3 text-[10px] uppercase tracking-label text-muted">
                                {{ $prenda->seccion?->nombre ?? 'Sin sección' }}
                            </span>
                        </div>
                        <span class="font-serif text-lg">{{ $prenda->precioTexto() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-app-layout>
