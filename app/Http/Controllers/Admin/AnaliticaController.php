<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoEvento;
use App\Http\Controllers\Controller;
use App\Models\EventoAnalitica;
use App\Models\Prenda;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AnaliticaController extends Controller
{
    /** Rangos que ofrece la cabecera. Cualquier otro valor cae en el de por defecto. */
    public const RANGOS = [7 => '7 días', 30 => '30 días', 90 => '90 días'];

    public const RANGO_POR_DEFECTO = 30;

    public function __invoke(Request $request): View
    {
        $dias = $request->integer('dias');
        $dias = array_key_exists($dias, self::RANGOS) ? $dias : self::RANGO_POR_DEFECTO;

        $hoy = CarbonImmutable::now()->startOfDay();
        $desde = $hoy->subDays($dias - 1);
        // Se consulta también el periodo anterior, de la misma longitud, para
        // poder mostrar la variación sin una segunda ronda de consultas.
        $desdeAnterior = $desde->subDays($dias);

        $porDia = $this->conteosPorDia($desdeAnterior);

        $serie = $this->rellenarDias($porDia, $desde, $hoy);
        $anterior = $this->rellenarDias($porDia, $desdeAnterior, $desde->subDay());

        $ranking = $this->ranking($desde);

        return view('admin.analitica', [
            'dias' => $dias,
            'rangos' => self::RANGOS,
            'desde' => $desde,
            'hasta' => $hoy,
            'serie' => $serie,
            'kpis' => $this->kpis($serie, $anterior, $ranking),
            'ranking' => $ranking,
            'reparto' => $this->reparto($serie),
            'porSeccion' => $this->porSeccion($desde),
            'hayDatos' => $serie->sum('visitas') + $serie->sum('selecciones') > 0,
        ]);
    }

    /**
     * Un registro por día y tipo. Se agrega en SQL y se completa en PHP: rellenar
     * los días sin eventos con generate_series ataría la consulta a Postgres sin
     * ganar nada, porque el rango nunca pasa de 180 filas.
     *
     * @return Collection<string, array{visitas: int, unicos: int, agregar: int, consultar: int}>
     */
    private function conteosPorDia(CarbonImmutable $desde): Collection
    {
        return EventoAnalitica::query()
            ->desde($desde)
            ->selectRaw('date(creado_en) as dia, tipo, count(*) as n, count(distinct visitante_hash) as unicos')
            ->groupBy('dia', 'tipo')
            ->get()
            ->groupBy('dia')
            ->map(function (Collection $delDia) {
                $de = fn (TipoEvento $t) => (int) ($delDia->firstWhere('tipo', $t)?->n ?? 0);

                return [
                    'visitas' => $de(TipoEvento::Visita),
                    'unicos' => (int) ($delDia->firstWhere('tipo', TipoEvento::Visita)?->unicos ?? 0),
                    'agregar' => $de(TipoEvento::Agregar),
                    'consultar' => $de(TipoEvento::Consultar),
                ];
            });
    }

    /**
     * @param  Collection<string, array<string, int>>  $porDia
     * @return Collection<int, array<string, mixed>>
     */
    private function rellenarDias(Collection $porDia, CarbonImmutable $desde, CarbonImmutable $hasta): Collection
    {
        $dias = collect();

        for ($dia = $desde; $dia->lessThanOrEqualTo($hasta); $dia = $dia->addDay()) {
            $conteos = $porDia->get($dia->toDateString(), [
                'visitas' => 0, 'unicos' => 0, 'agregar' => 0, 'consultar' => 0,
            ]);

            $selecciones = $conteos['agregar'] + $conteos['consultar'];

            $dias->push([
                'fecha' => $dia,
                ...$conteos,
                'selecciones' => $selecciones,
                // Tasa del día suelto. Un día sin visitas vale 0 y no null: la
                // gráfica necesita un número para dibujar la barra.
                'tasa' => $conteos['visitas'] > 0 ? $selecciones / $conteos['visitas'] * 100 : 0,
            ]);
        }

        return $dias;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $serie
     * @param  Collection<int, array<string, mixed>>  $anterior
     * @param  Collection<int, array<string, mixed>>  $ranking
     * @return array<string, mixed>
     */
    private function kpis(Collection $serie, Collection $anterior, Collection $ranking): array
    {
        $visitas = (int) $serie->sum('visitas');
        $selecciones = (int) $serie->sum('selecciones');

        return [
            'visitas' => $visitas,
            'visitasUnicas' => (int) $serie->sum('unicos'),
            'visitasVariacion' => $this->variacion($visitas, (int) $anterior->sum('visitas')),
            'selecciones' => $selecciones,
            'agregar' => (int) $serie->sum('agregar'),
            'consultar' => (int) $serie->sum('consultar'),
            'seleccionesVariacion' => $this->variacion($selecciones, (int) $anterior->sum('selecciones')),
            // Cuántas visitas acaban en interés por una prenda concreta.
            'tasa' => $visitas > 0 ? $selecciones / $visitas * 100 : null,
            'prendasDistintas' => $ranking->count(),
        ];
    }

    /** Variación porcentual contra el periodo anterior. null = no hay con qué comparar. */
    private function variacion(int $actual, int $previo): ?float
    {
        return $previo > 0 ? ($actual - $previo) / $previo * 100 : null;
    }

    /**
     * Top de prendas por selecciones, con el desglose de cada tipo.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function ranking(CarbonImmutable $desde, int $limite = 10): Collection
    {
        $conteos = EventoAnalitica::query()
            ->desde($desde)
            ->selecciones()
            ->whereNotNull('prenda_id')
            ->selectRaw('prenda_id, count(*) as total')
            ->selectRaw('count(*) filter (where tipo = ?) as agregar', [TipoEvento::Agregar->value])
            ->selectRaw('count(*) filter (where tipo = ?) as consultar', [TipoEvento::Consultar->value])
            ->groupBy('prenda_id')
            ->orderByDesc('total')
            ->limit($limite)
            ->get();

        $prendas = Prenda::with('seccion')->findMany($conteos->pluck('prenda_id'))->keyBy('id');

        return $conteos->map(fn ($fila) => [
            'prenda' => $prendas->get($fila->prenda_id),
            'total' => (int) $fila->total,
            'agregar' => (int) $fila->agregar,
            'consultar' => (int) $fila->consultar,
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $serie
     * @return array<int, array{rotulo: string, valor: int}>
     */
    private function reparto(Collection $serie): array
    {
        return [
            ['rotulo' => TipoEvento::Agregar->rotulo(), 'valor' => (int) $serie->sum('agregar')],
            ['rotulo' => TipoEvento::Consultar->rotulo(), 'valor' => (int) $serie->sum('consultar')],
        ];
    }

    /**
     * Selecciones agrupadas por la sección a la que pertenece la prenda.
     * Las prendas sin sección se agrupan aparte, como en el catálogo.
     *
     * @return array<int, array{rotulo: string, valor: int}>
     */
    private function porSeccion(CarbonImmutable $desde): array
    {
        return EventoAnalitica::query()
            ->desde($desde)
            ->selecciones()
            ->join('prendas', 'prendas.id', '=', 'eventos_analitica.prenda_id')
            ->leftJoin('secciones', 'secciones.id', '=', 'prendas.seccion_id')
            ->selectRaw('coalesce(secciones.nombre, ?) as rotulo, count(*) as valor', ['Sin sección'])
            ->groupBy('rotulo')
            ->orderByDesc('valor')
            ->get()
            ->map(fn ($fila) => ['rotulo' => $fila->rotulo, 'valor' => (int) $fila->valor])
            ->all();
    }
}
