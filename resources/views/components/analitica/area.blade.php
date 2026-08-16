@props(['serie', 'series', 'alto' => 'h-32'])

@php
    /** @var \Illuminate\Support\Collection $filas */
    $filas = collect($serie)->values();
    $n = $filas->count();

    $max = 1;
    foreach ($series as $s) {
        $max = max($max, (int) $filas->max($s['clave']));
    }

    // Lienzo en unidades del viewBox. Se estira con preserveAspectRatio="none",
    // y los trazos se mantienen finos con vector-effect, así que la deformación
    // sólo afecta a la posición de los puntos, que es justo lo que se quiere.
    $ancho = 640;
    $altoSvg = 150;
    $margen = 6;

    $x = fn (int $i) => $n > 1 ? round($i / ($n - 1) * $ancho, 2) : $ancho / 2;
    $y = fn (int $v) => round($altoSvg - $margen - ($v / $max) * ($altoSvg - $margen * 2), 2);

    $trazos = [];
    foreach ($series as $s) {
        $puntos = $filas->map(fn ($d, $i) => $x($i).' '.$y((int) $d[$s['clave']]))->all();
        $trazos[] = [
            'linea' => 'M '.implode(' L ', $puntos),
            'area' => 'M '.implode(' L ', $puntos)." L {$ancho} {$altoSvg} L 0 {$altoSvg} Z",
        ] + $s;
    }

    $ultimo = $n - 1;
    $marcas = array_values(array_unique([0, (int) round($ultimo / 3), (int) round($ultimo * 2 / 3), $ultimo]));
@endphp

<div class="a-chart {{ $alto }}">
    @foreach ([0, 25, 50, 75, 100] as $pct)
        <span class="a-chart-guia" style="bottom: {{ $pct }}%"></span>
    @endforeach

    <svg viewBox="0 0 {{ $ancho }} {{ $altoSvg }}" preserveAspectRatio="none"
         class="relative block h-full w-full overflow-visible" aria-hidden="true">
        @foreach ($trazos as $t)
            @if ($t['relleno'] ?? false)
                <path d="{{ $t['area'] }}" fill="{{ $t['color'] }}" fill-opacity="0.10" stroke="none" />
            @endif

            <path d="{{ $t['linea'] }}" fill="none" stroke="{{ $t['color'] }}" stroke-width="1.5"
                  stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"
                  @if ($t['discontinuo'] ?? false) stroke-dasharray="4 3" @endif />
        @endforeach
    </svg>
</div>

<div class="mt-2.5 flex justify-between text-[9px] uppercase tracking-label text-muted">
    @foreach ($marcas as $i)
        <span>{{ $filas[$i]['fecha']->isoFormat('D MMM') }}</span>
    @endforeach
</div>

<div class="mt-3 flex flex-wrap gap-4">
    @foreach ($trazos as $t)
        <span class="flex items-center gap-2 text-[10px] uppercase tracking-label text-muted">
            <span class="inline-block h-px w-5 {{ ($t['discontinuo'] ?? false) ? 'a-legend-punteada' : '' }}"
                  style="background-color: {{ $t['color'] }}"></span>
            {{ $t['rotulo'] }}
        </span>
    @endforeach
</div>
