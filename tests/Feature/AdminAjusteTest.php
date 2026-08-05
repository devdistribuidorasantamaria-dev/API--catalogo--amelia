<?php

namespace Tests\Feature;

use App\Models\Ajuste;
use App\Models\User;
use App\Services\Logotipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminAjusteTest extends TestCase
{
    use RefreshDatabase;

    public function test_sube_el_logotipo_y_guarda_sus_dimensiones(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->put('/admin/ajustes', [
                'subtitulo' => 'Colección · Manta',
                'logo' => UploadedFile::fake()->image('logo.png', 1200, 600),
            ])
            ->assertRedirect(route('admin.ajustes.edit'));

        $ruta = Ajuste::obtener('logo_ruta');

        $this->assertNotNull($ruta);
        Storage::disk('public')->assertExists($ruta);
        // Se reescala al ancho máximo de config('amelia.logo_ancho_max').
        $this->assertSame(720, (int) Ajuste::obtener('logo_ancho'));
        $this->assertSame(360, (int) Ajuste::obtener('logo_alto'));

        $this->getJson('/api/catalogo')
            ->assertOk()
            ->assertJsonPath('logo_url', Storage::disk('public')->url($ruta))
            ->assertJsonPath('logo_ancho', 720)
            ->assertJsonPath('logo_alto', 360);

        // La pantalla muestra la vista previa y la casilla para quitarlo.
        $this->get('/admin/ajustes')
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($ruta))
            ->assertSee('eliminar_logo', false);
    }

    public function test_reemplazar_el_logotipo_borra_el_archivo_anterior(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->put('/admin/ajustes', ['logo' => UploadedFile::fake()->image('uno.png', 800, 400)]);
        $primero = Ajuste::obtener('logo_ruta');

        $this->actingAs($admin)
            ->put('/admin/ajustes', ['logo' => UploadedFile::fake()->image('dos.png', 800, 400)]);
        $segundo = Ajuste::obtener('logo_ruta');

        $this->assertNotSame($primero, $segundo);
        Storage::disk('public')->assertMissing($primero);
        Storage::disk('public')->assertExists($segundo);
    }

    public function test_la_casilla_quita_el_logotipo_y_vuelve_al_texto(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->put('/admin/ajustes', ['logo' => UploadedFile::fake()->image('logo.png', 800, 400)]);
        $ruta = Ajuste::obtener('logo_ruta');

        $this->actingAs($admin)
            ->put('/admin/ajustes', ['eliminar_logo' => '1'])
            ->assertRedirect(route('admin.ajustes.edit'));

        Storage::disk('public')->assertMissing($ruta);
        $this->assertNull(app(Logotipo::class)->url());
        $this->getJson('/api/catalogo')->assertOk()->assertJsonPath('logo_url', null);
    }

    public function test_rechaza_un_archivo_que_no_es_imagen(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->put('/admin/ajustes', ['logo' => UploadedFile::fake()->create('catalogo.pdf', 40, 'application/pdf')])
            ->assertSessionHasErrors('logo');

        $this->assertNull(Ajuste::obtener('logo_ruta'));
    }
}
