@props(['filas'])

@php
    $max = max(1, (int) collect($filas)->max('total'));
@endphp

@if (collect($filas)->isEmpty())
    <p class="mt-6 border border-line px-4 py-10 text-center text-[13px] text-muted">
        Todavía nadie ha seleccionado una prenda en este periodo.
    </p>
@else
    <ol class="mt-5 divide-y divide-line border-y border-line">
        @foreach ($filas as $i => $fila)
            @php
                $prenda = $fila['prenda'];
                $ancho = fn (int $v) => round($v / $max * 100, 2);
            @endphp

            <li class="grid grid-cols-[1.75rem_minmax(0,1fr)_auto] items-center gap-x-4 py-3.5">
                <span class="font-serif text-[15px] italic text-muted">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>

                <div class="min-w-0">
                    <div class="flex items-baseline gap-3">
                        @if ($prenda)
                            <a href="{{ route('admin.prendas.edit', $prenda) }}"
                               class="truncate font-serif text-[17px] hover:underline">{{ $prenda->nombre }}</a>
                            <span class="shrink-0 text-[9px] uppercase tracking-label text-muted">
                                {{ $prenda->seccion?->nombre ?? 'Sin sección' }}
                            </span>
                        @else
                            {{-- La prenda se borró; el evento sobrevive con prenda_id nulo. --}}
                            <span class="truncate font-serif text-[17px] italic text-muted">Prenda eliminada</span>
                        @endif
                    </div>

                    {{-- Sólido = Agregar, tramado = Consultar. Sin color: la diferencia
                         se lee por textura, que es como se resuelve en monocromo. --}}
                    <div class="mt-2 flex h-1.5 w-full gap-px">
                        <span class="a-bar" style="width: {{ $ancho($fila['agregar']) }}%"
                              title="Agregar: {{ $fila['agregar'] }}"></span>
                        <span class="a-bar a-bar-trama" style="width: {{ $ancho($fila['consultar']) }}%"
                              title="Consultar: {{ $fila['consultar'] }}"></span>
                    </div>
                </div>

                <div class="text-right">
                    <span class="font-serif text-[20px] leading-none">{{ number_format($fila['total']) }}</span>
                    <p class="mt-1 text-[9px] uppercase tracking-label text-muted">
                        {{ $fila['agregar'] }} / {{ $fila['consultar'] }}
                    </p>
                </div>
            </li>
        @endforeach
    </ol>

    <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-[10px] uppercase tracking-label text-muted">
        <span class="flex items-center gap-2">
            <span class="a-bar inline-block h-1.5 w-5"></span> Agregar
        </span>
        <span class="flex items-center gap-2">
            <span class="a-bar a-bar-trama inline-block h-1.5 w-5"></span> Consultar
        </span>
        <span class="ml-auto">La cifra de la derecha es el total</span>
    </div>
@endif
