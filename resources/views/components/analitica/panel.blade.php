@props(['titulo', 'sub' => null])

{{-- Tarjeta del tablero. El fondo opaco es lo que deja ver los filetes del
     contenedor por los huecos de 1px de la rejilla. --}}
<section {{ $attributes->merge(['class' => 'flex flex-col bg-paper px-5 py-6']) }}>
    <header>
        <h2 class="font-serif text-[19px] font-normal leading-none">{{ $titulo }}</h2>

        @if ($sub)
            <p class="mt-2 text-[10px] uppercase tracking-label text-muted">{{ $sub }}</p>
        @endif
    </header>

    {{ $slot }}
</section>
