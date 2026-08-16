@props(['partes', 'centro' => null, 'rotuloCentro' => null])

@php
    $partes = collect($partes)->filter(fn ($p) => $p['valor'] > 0)->values();
    $total = (int) $partes->sum('valor');

    // Rampa de grises. El panel es siempre oscuro (no tiene el conmutador de tema
    // del catálogo), así que los valores van fijos y no por variable de tema.
    $grises = ['#f2f1ee', '#8f8f8f', '#5c5c5c', '#414141', '#303030'];

    $radio = 46;
    $circunferencia = 2 * M_PI * $radio;
    $acumulado = 0;

    $tramos = $partes->map(function ($parte, $i) use ($total, $circunferencia, $grises, &$acumulado) {
        $largo = $total > 0 ? $parte['valor'] / $total * $circunferencia : 0;
        $tramo = [
            'color' => $grises[$i % count($grises)],
            'largo' => round($largo, 3),
            'hueco' => round($circunferencia - $largo, 3),
            'desfase' => round(-$acumulado, 3),
            'porcentaje' => $total > 0 ? $parte['valor'] / $total * 100 : 0,
        ] + $parte;

        $acumulado += $largo;

        return $tramo;
    });
@endphp

@if ($total === 0)
    <p class="mt-6 border border-line px-4 py-10 text-center text-[13px] text-muted">Sin datos en este periodo.</p>
@else
    <div class="mt-5 flex items-center gap-6">
        <svg viewBox="0 0 120 120" class="h-[104px] w-[104px] shrink-0" role="img"
             aria-label="{{ $partes->map(fn ($p) => $p['rotulo'].': '.$p['valor'])->join(', ') }}">
            {{-- rotate(-90) para que el primer tramo arranque arriba y no a las 3. --}}
            <g transform="rotate(-90 60 60)" fill="none" stroke-width="13">
                @foreach ($tramos as $t)
                    <circle cx="60" cy="60" r="{{ $radio }}" stroke="{{ $t['color'] }}"
                            stroke-dasharray="{{ $t['largo'] }} {{ $t['hueco'] }}"
                            stroke-dashoffset="{{ $t['desfase'] }}"></circle>
                @endforeach
            </g>

            @if ($centro !== null)
                <text x="60" y="{{ $rotuloCentro ? 57 : 63 }}" text-anchor="middle"
                      class="fill-ink font-serif" font-size="21">{{ $centro }}</text>

                @if ($rotuloCentro)
                    <text x="60" y="73" text-anchor="middle" class="fill-muted"
                          font-size="7.5" letter-spacing="1.4">{{ Str::upper($rotuloCentro) }}</text>
                @endif
            @endif
        </svg>

        <ul class="min-w-0 flex-1 space-y-2.5">
            @foreach ($tramos as $t)
                <li class="flex items-baseline gap-2.5">
                    <span class="mt-1 inline-block h-2 w-2 shrink-0" style="background-color: {{ $t['color'] }}"></span>
                    <span class="truncate text-[11px] uppercase tracking-label text-muted">{{ $t['rotulo'] }}</span>
                    <span class="ml-auto shrink-0 font-serif text-[15px]">{{ number_format($t['valor']) }}</span>
                    <span class="w-11 shrink-0 text-right text-[10px] tracking-label text-muted">
                        {{ number_format($t['porcentaje'], 1) }}%
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
