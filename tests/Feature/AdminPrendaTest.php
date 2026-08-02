<?php

namespace Tests\Feature;

use App\Models\Prenda;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPrendaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_el_panel_requiere_sesion(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/prendas')->assertRedirect('/login');
    }

    public function test_las_pantallas_del_panel_cargan(): void
    {
        $this->actingAs($this->admin());
        Prenda::create(['nombre' => 'Vestido midi', 'tallas' => ['M'], 'precio_desde' => 38]);

        $this->get('/admin')->assertOk()->assertSee('Resumen');
        $this->get('/admin/prendas')->assertOk()->assertSee('Vestido midi');
        $this->get('/admin/prendas/create')->assertOk()->assertSee('Nueva prenda');
        $this->get('/admin/secciones')->assertOk();
        $this->get('/admin/ajustes')->assertOk();
    }

    public function test_crea_una_prenda_con_fotos_y_normaliza_las_tallas(): void
    {
        Storage::fake('public');
        $seccion = Seccion::create(['nombre' => 'Vestidos']);

        $this->actingAs($this->admin())
            ->post('/admin/prendas', [
                'nombre' => 'Vestido midi plisado',
                'seccion_id' => $seccion->id,
                'descripcion' => 'Gasa plisada.',
                'tallas' => ' XS , S ,, S , M ',
                'precio_desde' => '38',
                'precio_hasta' => '45',
                'activa' => '1',
                'fotos' => [
                    UploadedFile::fake()->image('a.jpg', 2000, 3000),
                    UploadedFile::fake()->image('b.jpg', 800, 1000),
                ],
            ])
            ->assertRedirect();

        $prenda = Prenda::firstWhere('nombre', 'Vestido midi plisado');

        $this->assertNotNull($prenda);
        $this->assertSame(['XS', 'S', 'M'], $prenda->tallas, 'Tallas: se recortan, se quitan vacías y duplicadas.');
        $this->assertSame('vestido-midi-plisado', $prenda->slug);
        $this->assertSame('$38.00 – $45.00', $prenda->precioTexto());
        $this->assertCount(2, $prenda->imagenes);
        $this->assertSame([0, 1], $prenda->imagenes->pluck('orden')->all());

        foreach ($prenda->imagenes as $imagen) {
            Storage::disk('public')->assertExists($imagen->ruta);
        }
    }

    public function test_rechaza_un_rango_de_precio_invertido(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/prendas', [
                'nombre' => 'Blusa',
                'precio_desde' => '40',
                'precio_hasta' => '20',
            ])
            ->assertSessionHasErrors('precio_hasta');
    }

    public function test_reordenar_y_eliminar_fotos_deja_el_orden_sin_huecos(): void
    {
        Storage::fake('public');
        $prenda = Prenda::create(['nombre' => 'Blusa satinada', 'tallas' => []]);

        foreach (range(0, 2) as $i) {
            $prenda->imagenes()->create(['ruta' => "prendas/f{$i}.jpg", 'orden' => $i]);
            Storage::disk('public')->put("prendas/f{$i}.jpg", 'x');
        }

        [$a, $b, $c] = $prenda->imagenes->pluck('id')->all();

        $this->actingAs($this->admin())
            ->put("/admin/prendas/{$prenda->id}", [
                'nombre' => 'Blusa satinada',
                'eliminar_imagenes' => [$a],
                // c pasa a ser portada; a se elimina aunque venga en el orden.
                'orden_imagenes' => [$c, $a, $b],
            ])
            ->assertRedirect();

        $restantes = $prenda->fresh()->imagenes;

        $this->assertSame([$c, $b], $restantes->pluck('id')->all());
        $this->assertSame([0, 1], $restantes->pluck('orden')->all());
        Storage::disk('public')->assertMissing('prendas/f0.jpg');
    }

    public function test_eliminar_una_prenda_borra_sus_fotos(): void
    {
        Storage::fake('public');
        $prenda = Prenda::create(['nombre' => 'Pañuelo', 'tallas' => []]);
        $prenda->imagenes()->create(['ruta' => 'prendas/p.jpg', 'orden' => 0]);
        Storage::disk('public')->put('prendas/p.jpg', 'x');

        $this->actingAs($this->admin())
            ->delete("/admin/prendas/{$prenda->id}")
            ->assertRedirect('/admin/prendas');

        $this->assertModelMissing($prenda);
        $this->assertSame(0, $prenda->imagenes()->count());
        Storage::disk('public')->assertMissing('prendas/p.jpg');
    }

    public function test_eliminar_una_seccion_deja_sus_prendas_sin_seccion(): void
    {
        $seccion = Seccion::create(['nombre' => 'Vestidos']);
        $prenda = Prenda::create(['nombre' => 'Vestido', 'seccion_id' => $seccion->id, 'tallas' => []]);

        $this->actingAs($this->admin())
            ->delete("/admin/secciones/{$seccion->id}")
            ->assertRedirect('/admin/secciones');

        $this->assertNull($prenda->fresh()->seccion_id);
    }
}
