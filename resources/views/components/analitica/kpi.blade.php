@props(['rotulo', 'valor', 'variacion' => null, 'nota' => null])

{{-- Cifra grande del tablero. La variación se marca con signo, no con color:
     el panel es monocromo y un verde/rojo desentonaría con el catálogo. --}}
<div class="bg-paper px-4 py-4">
    <span class="a-eyebrow">{{ $rotulo }}</span>

    <p class="mt-2.5 font-serif text-[26px] leading-none">{{ $valor }}</p>

    @if (! is_null($variacion))
        <p class="mt-2 text-[10px] uppercase tracking-label text-muted">
            <span class="{{ $variacion >= 0 ? 'text-ink' : 'text-muted' }}">
                {{ $variacion >= 0 ? '+' : '-' }}{{ number_format(abs($variacion), 1) }}%
            </span>
            <span class="ml-1">vs. anterior</span>
        </p>
    @elseif (! is_null($nota))
        <p class="mt-2 text-[10px] uppercase tracking-label text-muted">{{ $nota }}</p>
    @endif
</div>
