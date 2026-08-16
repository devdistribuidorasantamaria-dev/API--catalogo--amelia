<x-app-layout title="Analítica" eyebrow="Panel" heading="Analítica">
    <x-slot name="actions">
        {{-- Selector de rango. Enlaces y no formulario: así el periodo queda en la
             URL y se puede compartir o recargar sin perderlo. --}}
        <div class="flex border border-line">
            @foreach ($rangos as $valor => $rotulo)
                <a href="{{ route('admin.analitica', ['dias' => $valor]) }}"
                   @class([
                       'px-3.5 py-2.5 text-[10px] uppercase tracking-label transition-colors',
                       'bg-ink text-paper' => $dias === $valor,
                       'text-muted hover:text-ink' => $dias !== $valor,
                   ])>{{ $rotulo }}</a>
            @endforeach
        </div>
    </x-slot>

    @if (! $hayDatos)
        <div class="border border-line px-6 py-16 text-center">
            <p class="font-serif text-2xl italic">Aún no hay eventos</p>
            <p class="mx-auto mt-3 max-w-md text-[13px] leading-relaxed text-muted">
                El catálogo empieza a registrar visitas y selecciones en cuanto alguien lo abre.
                Para ver el tablero con datos de prueba en local:
                <code class="mt-3 block text-ink">php artisan db:seed --class=AnaliticaDemoSeeder</code>
            </p>
        </div>
    @else
        {{-- Un solo bloque con fondo de filete: los huecos de 1px entre paneles son
             los que dibujan las separaciones, así que el tablero se lee como una
             sola pieza y no como tarjetas sueltas. --}}
        <div class="border border-line bg-line">
            <div class="grid gap-px lg:grid-cols-3">
                <x-analitica.panel titulo="Visitas al catálogo" sub="por día">
                    <div class="mt-5 grid grid-cols-2 gap-px bg-line">
                        <x-analitica.kpi
                            rotulo="Visitas"
                            :valor="number_format($kpis['visitas'])"
                            :variacion="$kpis['visitasVariacion']" />
                        <x-analitica.kpi
                            rotulo="Únicas"
                            :valor="number_format($kpis['visitasUnicas'])"
                            nota="visitantes distintos" />
                    </div>

                    <div class="mt-6">
                        <x-analitica.barras :serie="$serie" clave="visitas" />
                    </div>
                </x-analitica.panel>

                <x-analitica.panel titulo="Selecciones totales" sub="agregar + consultar, por día">
                    <div class="mt-5 grid grid-cols-2 gap-px bg-line">
                        <x-analitica.kpi
                            rotulo="Selecciones"
                            :valor="number_format($kpis['selecciones'])"
                            :variacion="$kpis['seleccionesVariacion']" />
                        <x-analitica.kpi
                            rotulo="Agregar / Consultar"
                            :valor="number_format($kpis['agregar']).' / '.number_format($kpis['consultar'])"
                            nota="desglose del periodo" />
                    </div>

                    <div class="mt-6">
                        <x-analitica.area
                            :serie="$serie"
                            :series="[
                                ['clave' => 'selecciones', 'rotulo' => 'Total', 'color' => '#f2f1ee', 'relleno' => true],
                                ['clave' => 'agregar', 'rotulo' => 'Agregar', 'color' => '#8f8f8f'],
                                ['clave' => 'consultar', 'rotulo' => 'Consultar', 'color' => '#8f8f8f', 'discontinuo' => true],
                            ]" />
                    </div>
                </x-analitica.panel>

                <x-analitica.panel titulo="Interés" sub="selecciones sobre visitas, por día">
                    <div class="mt-5 grid grid-cols-2 gap-px bg-line">
                        <x-analitica.kpi
                            rotulo="Tasa"
                            :valor="is_null($kpis['tasa']) ? '—' : number_format($kpis['tasa'], 1).'%'"
                            nota="de las visitas" />
                        <x-analitica.kpi
                            rotulo="Prendas"
                            :valor="number_format($kpis['prendasDistintas'])"
                            nota="con selecciones" />
                    </div>

                    <div class="mt-6">
                        <x-analitica.barras :serie="$serie" clave="tasa" :decimales="1" sufijo="%" />
                    </div>
                </x-analitica.panel>
            </div>

            <div class="mt-px grid gap-px lg:grid-cols-[1.7fr_1fr]">
                <x-analitica.panel titulo="Prendas más seleccionadas" sub="top 10 por clics de agregar y consultar">
                    <x-analitica.ranking :filas="$ranking" />
                </x-analitica.panel>

                {{-- Dos anillos apilados: así la columna iguala el alto del ranking
                     en vez de dejar medio panel vacío. --}}
                <div class="grid gap-px">
                    <x-analitica.panel titulo="Agregar vs. Consultar" sub="reparto del interés">
                        <x-analitica.dona
                            :partes="$reparto"
                            :centro="number_format($kpis['selecciones'])"
                            rotulo-centro="total" />
                    </x-analitica.panel>

                    <x-analitica.panel titulo="Reparto por sección" sub="selecciones agrupadas por capítulo">
                        <x-analitica.dona
                            :partes="$porSeccion"
                            :centro="number_format(collect($porSeccion)->sum('valor'))"
                            rotulo-centro="total" />
                    </x-analitica.panel>
                </div>
            </div>
        </div>

        <p class="mt-6 text-[10px] uppercase tracking-label text-muted">
            {{ $desde->isoFormat('D MMM YYYY') }} — {{ $hasta->isoFormat('D MMM YYYY') }}
            &middot; datos anónimos, sin IP ni identificadores personales
        </p>
    @endif
</x-app-layout>
