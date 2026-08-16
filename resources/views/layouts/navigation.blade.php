@php
    $enlaces = [
        'admin.dashboard' => 'Resumen',
        'admin.prendas.index' => 'Prendas',
        'admin.secciones.index' => 'Secciones',
        'admin.analitica' => 'Analítica',
        'admin.ajustes.edit' => 'Ajustes',
    ];

    $activo = function (string $ruta) {
        // admin.prendas.index → admin.prendas.* para que create/edit también marquen.
        return request()->routeIs($ruta) || request()->routeIs(str_replace('.index', '.*', $ruta));
    };
@endphp

<nav x-data="{ abierto: false }" class="sticky top-0 z-40 border-b border-line bg-black/85 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-6xl items-center gap-8 px-6 sm:px-8">
        <a href="{{ route('admin.dashboard') }}" class="whitespace-nowrap font-serif text-xl tracking-wide text-ink">
            Amelia <span class="text-muted">·</span> <span class="text-[11px] uppercase tracking-label">Panel</span>
        </a>

        <div class="hidden flex-1 items-center gap-6 sm:flex">
            @foreach ($enlaces as $ruta => $texto)
                <a href="{{ route($ruta) }}"
                   @class([
                       'text-[11px] uppercase tracking-label transition-colors',
                       'text-ink' => $activo($ruta),
                       'text-muted hover:text-ink' => ! $activo($ruta),
                   ])>{{ $texto }}</a>
            @endforeach
        </div>

        <div class="ml-auto hidden items-center gap-4 sm:flex">
            <a href="{{ config('amelia.frontend_url') }}" target="_blank" rel="noopener"
               class="text-[11px] uppercase tracking-label text-muted hover:text-ink">Ver catálogo ↗</a>
            <span class="text-line">|</span>
            <a href="{{ route('profile.edit') }}" class="text-[11px] uppercase tracking-label text-muted hover:text-ink">
                {{ Str::limit(Auth::user()->name, 14) }}
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-[11px] uppercase tracking-label text-muted hover:text-ink">Salir</button>
            </form>
        </div>

        <button @click="abierto = ! abierto" class="ml-auto text-ink sm:hidden" aria-label="Menú">
            <span x-show="! abierto">☰</span>
            <span x-show="abierto" x-cloak>✕</span>
        </button>
    </div>

    <div x-show="abierto" x-cloak class="border-t border-line px-6 py-4 sm:hidden">
        @foreach ($enlaces as $ruta => $texto)
            <a href="{{ route($ruta) }}" class="block py-2 text-[11px] uppercase tracking-label text-muted hover:text-ink">{{ $texto }}</a>
        @endforeach
        <a href="{{ config('amelia.frontend_url') }}" target="_blank" rel="noopener"
           class="block py-2 text-[11px] uppercase tracking-label text-muted hover:text-ink">Ver catálogo ↗</a>
        <a href="{{ route('profile.edit') }}" class="block py-2 text-[11px] uppercase tracking-label text-muted hover:text-ink">Mi cuenta</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="block py-2 text-[11px] uppercase tracking-label text-muted hover:text-ink">Salir</button>
        </form>
    </div>
</nav>
