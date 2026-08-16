<?php

namespace Tests\Feature;

use App\Enums\TipoEvento;
use App\Models\EventoAnalitica;
use App\Models\Prenda;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnaliticaTest extends TestCase
{
    use RefreshDatabase;

    private function evento(TipoEvento $tipo, ?Prenda $prenda, int $diasAtras, string $visitante = 'a'): void
    {
        EventoAnalitica::create([
            'tipo' => $tipo,
            'prenda_id' => $prenda?->id,
            'visitante_hash' => hash('sha256', $visitante),
            'creado_en' => now()->subDays($diasAtras)->setTime(12, 0),
        ]);
    }

    public function test_el_panel_exige_sesion(): void
    {
        $this->get('/admin/analitica')->assertRedirect('/login');
    }

    public function test_muestra_el_aviso_cuando_no_hay_eventos(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/analitica')
            ->assertOk()
            ->assertSee('Aún no hay eventos')
            ->assertSee('AnaliticaDemoSeeder');
    }

    public function test_suma_las_visitas_y_las_selecciones_del_periodo(): void
    {
        $prenda = Prenda::create(['nombre' => 'Vestido midi', 'tallas' => ['M'], 'precio_desde' => 38]);

        $this->evento(TipoEvento::Visita, null, 1, 'ana');
        $this->evento(TipoEvento::Visita, null, 1, 'ana');   // misma huella: 2 visitas, 1 única
        $this->evento(TipoEvento::Visita, null, 2, 'beto');
        $this->evento(TipoEvento::Agregar, $prenda, 1);
        $this->evento(TipoEvento::Consultar, $prenda, 1);

        $vista = $this->actingAs(User::factory()->create())
            ->get('/admin/analitica?dias=30')
            ->assertOk()
            ->viewData('kpis');

        $this->assertSame(3, $vista['visitas']);
        $this->assertSame(2, $vista['visitasUnicas']);
        $this->assertSame(2, $vista['selecciones']);
        $this->assertSame(1, $vista['agregar']);
        $this->assertSame(1, $vista['consultar']);
        $this->assertSame(1, $vista['prendasDistintas']);
        $this->assertEqualsWithDelta(66.67, $vista['tasa'], 0.01);
    }

    public function test_el_rango_recorta_lo_que_queda_fuera(): void
    {
        $this->evento(TipoEvento::Visita, null, 2);
        $this->evento(TipoEvento::Visita, null, 40);

        $usuario = User::factory()->create();

        $this->assertSame(1, $this->actingAs($usuario)->get('/admin/analitica?dias=7')->viewData('kpis')['visitas']);
        $this->assertSame(2, $this->actingAs($usuario)->get('/admin/analitica?dias=90')->viewData('kpis')['visitas']);
    }

    public function test_un_rango_invalido_cae_en_el_de_por_defecto(): void
    {
        $respuesta = $this->actingAs(User::factory()->create())->get('/admin/analitica?dias=999');

        $respuesta->assertOk();
        $this->assertSame(30, $respuesta->viewData('dias'));
        // La serie trae un punto por día aunque no haya eventos.
        $this->assertCount(30, $respuesta->viewData('serie'));
    }

    public function test_ordena_el_ranking_por_selecciones_y_desglosa_los_dos_tipos(): void
    {
        $popular = Prenda::create(['nombre' => 'Blusa seda', 'tallas' => [], 'precio_desde' => 22]);
        $otra = Prenda::create(['nombre' => 'Pañuelo', 'tallas' => [], 'precio_desde' => 15]);

        foreach (range(1, 3) as $i) {
            $this->evento(TipoEvento::Agregar, $popular, 1);
        }
        $this->evento(TipoEvento::Consultar, $popular, 1);
        $this->evento(TipoEvento::Agregar, $otra, 1);

        $ranking = $this->actingAs(User::factory()->create())
            ->get('/admin/analitica')
            ->viewData('ranking');

        $this->assertSame('Blusa seda', $ranking[0]['prenda']->nombre);
        $this->assertSame(4, $ranking[0]['total']);
        $this->assertSame(3, $ranking[0]['agregar']);
        $this->assertSame(1, $ranking[0]['consultar']);
        $this->assertSame('Pañuelo', $ranking[1]['prenda']->nombre);
    }

    public function test_agrupa_por_seccion_y_separa_las_prendas_sueltas(): void
    {
        $vestidos = Seccion::create(['nombre' => 'Vestidos', 'orden' => 1]);
        $conSeccion = Prenda::create(['nombre' => 'Vestido midi', 'seccion_id' => $vestidos->id, 'tallas' => [], 'precio_desde' => 38]);
        $suelta = Prenda::create(['nombre' => 'Pañuelo', 'tallas' => [], 'precio_desde' => 15]);

        $this->evento(TipoEvento::Agregar, $conSeccion, 1);
        $this->evento(TipoEvento::Consultar, $conSeccion, 1);
        $this->evento(TipoEvento::Agregar, $suelta, 1);

        $porSeccion = $this->actingAs(User::factory()->create())
            ->get('/admin/analitica')
            ->viewData('porSeccion');

        $this->assertSame([
            ['rotulo' => 'Vestidos', 'valor' => 2],
            ['rotulo' => 'Sin sección', 'valor' => 1],
        ], $porSeccion);
    }

    public function test_conserva_en_el_ranking_los_eventos_de_una_prenda_borrada(): void
    {
        $prenda = Prenda::create(['nombre' => 'Descatalogada', 'tallas' => [], 'precio_desde' => 20]);

        $this->evento(TipoEvento::Agregar, $prenda, 1);
        $prenda->delete();

        $ranking = $this->actingAs(User::factory()->create())
            ->get('/admin/analitica')
            ->viewData('ranking');

        // El evento sobrevive con prenda_id nulo, así que no entra en el ranking
        // por prenda, pero sí sigue contando como selección del periodo.
        $this->assertCount(0, $ranking);
        $this->assertSame(1, $this->actingAs(User::factory()->create())
            ->get('/admin/analitica')->viewData('kpis')['selecciones']);
    }

    public function test_calcula_la_variacion_contra_el_periodo_anterior(): void
    {
        // Periodo actual (últimos 7 días): 3 visitas. Anterior (días 7..13): 2.
        foreach ([1, 2, 3] as $d) {
            $this->evento(TipoEvento::Visita, null, $d);
        }
        foreach ([8, 9] as $d) {
            $this->evento(TipoEvento::Visita, null, $d);
        }

        $kpis = $this->actingAs(User::factory()->create())
            ->get('/admin/analitica?dias=7')
            ->viewData('kpis');

        $this->assertSame(3, $kpis['visitas']);
        $this->assertEqualsWithDelta(50.0, $kpis['visitasVariacion'], 0.01);
    }

    public function test_el_menu_del_panel_enlaza_la_analitica(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee(route('admin.analitica'), false);
    }
}
