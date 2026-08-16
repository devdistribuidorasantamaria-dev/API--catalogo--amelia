@props(['serie', 'clave' => 'visitas', 'alto' => 'h-32', 'decimales' => 0, 'sufijo' => ''])

@php
    $filas = collect($serie)->values();
    $max = max(0.0001, (float) $filas->max($clave));

    // Con 90 días no caben todas las fechas debajo: se rotulan cuatro repartidas.
    $ultimo = $filas->count() - 1;
    $marcas = array_values(array_unique([0, (int) round($ultimo / 3), (int) round($ultimo * 2 / 3), $ultimo]));
@endphp

{{-- Barras en CSS y no en SVG: así el trazo de los filetes y el texto quedan
     nítidos a cualquier ancho, sin depender de escalados del viewBox. --}}
<div class="a-chart {{ $alto }}">
    @foreach ([0, 25, 50, 75, 100] as $pct)
        <span class="a-chart-guia" style="bottom: {{ $pct }}%"></span>
    @endforeach

    <div class="relative flex h-full items-end gap-px">
        @foreach ($filas as $d)
            <div class="group relative flex h-full flex-1 items-end">
                {{-- Un día con actividad nunca debe verse como uno vacío: mínimo 2%. --}}
                <span class="a-bar w-full"
                      style="height: {{ $d[$clave] > 0 ? max(2, round($d[$clave] / $max * 100, 2)) : 0 }}%"></span>

                <span class="a-tooltip">
                    {{ $d['fecha']->isoFormat('ddd D MMM') }} · {{ number_format($d[$clave], $decimales) }}{{ $sufijo }}
                </span>
            </div>
        @endforeach
    </div>
</div>

<div class="mt-2.5 flex justify-between text-[9px] uppercase tracking-label text-muted">
    @foreach ($marcas as $i)
        <span>{{ $filas[$i]['fecha']->isoFormat('D MMM') }}</span>
    @endforeach
</div>
