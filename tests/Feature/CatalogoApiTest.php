<?php

namespace Tests\Feature;

use App\Models\Prenda;
use App\Models\Seccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_agrupa_las_prendas_en_bloques_respetando_el_orden_de_secciones(): void
    {
        $blusas = Seccion::create(['nombre' => 'Blusas', 'orden' => 2]);
        $vestidos = Seccion::create(['nombre' => 'Vestidos', 'orden' => 1]);

        Prenda::create(['nombre' => 'Pañuelo', 'tallas' => [], 'precio_desde' => 15]);
        Prenda::create(['nombre' => 'Blusa globo', 'seccion_id' => $blusas->id, 'tallas' => ['S'], 'precio_desde' => 24.5]);
        Prenda::create(['nombre' => 'Vestido midi', 'seccion_id' => $vestidos->id, 'tallas' => ['M'], 'precio_desde' => 38, 'precio_hasta' => 45]);

        $respuesta = $this->getJson('/api/catalogo')->assertOk();

        $bloques = $respuesta->json('bloques');

        $this->assertNull($bloques[0]['seccion'], 'Las prendas sin sección van primero.');
        $this->assertSame('Vestidos', $bloques[1]['seccion']['nombre']);
        $this->assertSame('Blusas', $bloques[2]['seccion']['nombre']);
        $this->assertSame(3, $respuesta->json('total_prendas'));
        $this->assertSame('$38.00 – $45.00', $bloques[1]['prendas'][0]['precio_texto']);
    }

    public function test_omite_las_prendas_ocultas(): void
    {
        Prenda::create(['nombre' => 'Oculta', 'tallas' => [], 'activa' => false]);

        $this->getJson('/api/catalogo')
            ->assertOk()
            ->assertJsonPath('total_prendas', 0)
            ->assertJsonPath('bloques', []);
    }

    public function test_una_prenda_oculta_no_es_accesible_por_slug(): void
    {
        $prenda = Prenda::create(['nombre' => 'Oculta', 'tallas' => [], 'activa' => false]);

        $this->getJson("/api/prendas/{$prenda->slug}")->assertNotFound();
    }
}
