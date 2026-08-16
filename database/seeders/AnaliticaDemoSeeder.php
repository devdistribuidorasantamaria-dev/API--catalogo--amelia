<?php

namespace Database\Seeders;

use App\Enums\TipoEvento;
use App\Models\EventoAnalitica;
use App\Models\Prenda;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Eventos de prueba para poder ver el panel de Analítica con datos en local.
 *
 * No está registrado en DatabaseSeeder a propósito: se llama a mano con
 * `php artisan db:seed --class=AnaliticaDemoSeeder`. Borra los eventos que haya
 * antes de generar los nuevos, así que sólo tiene sentido en desarrollo.
 *
 * La generación es determinista (mt_srand con semilla fija): dos ejecuciones
 * dan el mismo dashboard, que es lo que se quiere para comparar cambios de
 * maquetado sin que las cifras bailen.
 */
class AnaliticaDemoSeeder extends Seeder
{
    private const DIAS = 90;

    private const SEMILLA = 20260816;

    /** Reparto entre agregar y consultar: agregar pesa más porque no sale de la página. */
    private const PORCENTAJE_AGREGAR = 62;

    /**
     * Peso relativo de cada hora del día (0..23). Dos picos: la pausa del
     * mediodía y la noche, que es cuando de verdad se mira un catálogo.
     */
    private const HORAS = [
        1, 1, 1, 1, 1, 2, 4, 8, 14, 18, 22, 26,
        34, 32, 24, 22, 24, 30, 40, 52, 58, 50, 30, 14,
    ];

    public function run(): void
    {
        if (app()->isProduction() && ! $this->forzado()) {
            $this->command?->error('AnaliticaDemoSeeder no se ejecuta en producción. Usa --force si de verdad lo quieres.');

            return;
        }

        $prendas = Prenda::query()->activas()->ordenadas()->pluck('id')->all();

        if ($prendas === []) {
            $this->command?->warn('No hay prendas publicadas: siembra primero el catálogo.');

            return;
        }

        if (! $this->confirmarBorrado()) {
            return;
        }

        EventoAnalitica::truncate();

        mt_srand(self::SEMILLA);

        $pesos = $this->pesosDePrendas($prendas);
        $lote = [];
        $total = 0;
        $hoy = CarbonImmutable::now()->startOfDay();

        for ($atras = self::DIAS - 1; $atras >= 0; $atras--) {
            $dia = $hoy->subDays($atras);
            $visitas = $this->visitasDelDia($dia, 1 - $atras / self::DIAS);

            // Un mismo visitante puede volver el mismo día: ~78 % de las visitas
            // son de alguien distinto, el resto son regresos o recargas.
            $visitantes = $this->visitantesDelDia($dia, max(1, (int) round($visitas * 0.78)));

            for ($i = 0; $i < $visitas; $i++) {
                $lote[] = $this->fila(TipoEvento::Visita, null, $this->momento($dia), $this->uno($visitantes));
            }

            // Entre el 6 % y el 13 % de las visitas acaban en una selección.
            $selecciones = (int) round($visitas * mt_rand(60, 130) / 1000);

            for ($i = 0; $i < $selecciones; $i++) {
                $tipo = mt_rand(1, 100) <= self::PORCENTAJE_AGREGAR ? TipoEvento::Agregar : TipoEvento::Consultar;
                $lote[] = $this->fila($tipo, $this->porPeso($pesos), $this->momento($dia), $this->uno($visitantes));
            }

            if (count($lote) >= 1000) {
                $total += $this->volcar($lote);
            }
        }

        $total += $this->volcar($lote);

        $this->command?->info("Analítica de prueba: {$total} eventos en ".self::DIAS.' días.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $lote
     */
    private function volcar(array &$lote): int
    {
        if ($lote === []) {
            return 0;
        }

        $n = count($lote);
        DB::table('eventos_analitica')->insert($lote);
        $lote = [];

        return $n;
    }

    /**
     * @return array<string, mixed>
     */
    private function fila(TipoEvento $tipo, ?int $prendaId, CarbonImmutable $momento, string $visitante): array
    {
        return [
            'tipo' => $tipo->value,
            'prenda_id' => $prendaId,
            'visitante_hash' => $visitante,
            'creado_en' => $momento->toDateTimeString(),
        ];
    }

    /** Tendencia creciente + curva semanal + ruido. */
    private function visitasDelDia(CarbonImmutable $dia, float $progreso): int
    {
        $base = 10 + 46 * $progreso;

        $factor = match ($dia->dayOfWeek) {
            CarbonImmutable::SATURDAY, CarbonImmutable::SUNDAY => 1.35,
            CarbonImmutable::MONDAY => 0.72,
            default => 1.0,
        };

        return max(1, (int) round($base * $factor * mt_rand(75, 125) / 100));
    }

    /**
     * Huellas de los visitantes del día. Derivadas de la fecha, no aleatorias,
     * para que el seeder siga siendo reproducible.
     *
     * @return array<int, string>
     */
    private function visitantesDelDia(CarbonImmutable $dia, int $cuantos): array
    {
        $huellas = [];

        for ($i = 0; $i < $cuantos; $i++) {
            $huellas[] = hash('sha256', $dia->toDateString().'|demo|'.$i);
        }

        return $huellas;
    }

    private function momento(CarbonImmutable $dia): CarbonImmutable
    {
        return $dia->addHours($this->porPeso(self::HORAS))
            ->addMinutes(mt_rand(0, 59))
            ->addSeconds(mt_rand(0, 59));
    }

    /**
     * Pesos decrecientes por posición: unas pocas prendas se llevan el grueso de
     * las selecciones, como pasa de verdad. Sin esto el ranking sale plano.
     *
     * @param  array<int, int>  $prendas
     * @return array<int, int> id de prenda => peso
     */
    private function pesosDePrendas(array $prendas): array
    {
        $pesos = [];

        foreach (array_values($prendas) as $posicion => $id) {
            $pesos[$id] = (int) round(1000 / (($posicion + 1) ** 0.85));
        }

        return $pesos;
    }

    /**
     * Elige una clave al azar respetando los pesos.
     *
     * @param  array<int, int>  $pesos
     */
    private function porPeso(array $pesos): int
    {
        $tirada = mt_rand(1, array_sum($pesos));

        foreach ($pesos as $clave => $peso) {
            $tirada -= $peso;

            if ($tirada <= 0) {
                return $clave;
            }
        }

        return (int) array_key_first($pesos);
    }

    /**
     * @param  array<int, string>  $opciones
     */
    private function uno(array $opciones): string
    {
        return $opciones[mt_rand(0, count($opciones) - 1)];
    }

    private function forzado(): bool
    {
        return (bool) ($this->command?->option('force') ?? false);
    }

    private function confirmarBorrado(): bool
    {
        $existentes = EventoAnalitica::count();

        if ($existentes === 0 || $this->forzado()) {
            return true;
        }

        if (! ($this->command?->getOutput()->isInteractive() ?? false)) {
            $this->command?->error("Hay {$existentes} eventos guardados. Ejecuta con --force para reemplazarlos.");

            return false;
        }

        return $this->command->confirm("Se borrarán los {$existentes} eventos existentes. ¿Continuar?", false);
    }
}
