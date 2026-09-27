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

    public function test_guarda_las_redes_sociales_completando_lo_que_falta(): void
    {
        $this->actingAs(User::factory()->create())
            ->put('/admin/ajustes', [
                'redes' => [
                    'facebook' => 'https://facebook.com/ameliaboutique',
                    // Sin protocolo y sólo el usuario: se completan al guardar.
                    'instagram' => 'instagram.com/ameliaboutique',
                    'tiktok' => '@ameliaboutique',
                ],
            ])
            ->assertRedirect(route('admin.ajustes.edit'));

        $this->assertSame('https://facebook.com/ameliaboutique', Ajuste::obtener('red_facebook'));
        $this->assertSame('https://instagram.com/ameliaboutique', Ajuste::obtener('red_instagram'));
        $this->assertSame('https://tiktok.com/@ameliaboutique', Ajuste::obtener('red_tiktok'));

        $this->getJson('/api/catalogo')
            ->assertOk()
            ->assertJsonPath('redes.0.red', 'facebook')
            ->assertJsonPath('redes.1.nombre', 'Instagram')
            ->assertJsonPath('redes.2.url', 'https://tiktok.com/@ameliaboutique');
    }

    public function test_una_red_vacia_desaparece_del_catalogo(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->put('/admin/ajustes', ['redes' => ['instagram' => '@amelia']]);

        $this->getJson('/api/catalogo')->assertOk()->assertJsonCount(1, 'redes');

        $this->actingAs($admin)
            ->put('/admin/ajustes', ['redes' => ['instagram' => '']]);

        $this->assertNull(Ajuste::obtener('red_instagram'));
        $this->getJson('/api/catalogo')->assertOk()->assertJsonPath('redes', []);
    }

    public function test_rechaza_una_direccion_de_red_que_no_se_entiende(): void
    {
        $this->actingAs(User::factory()->create())
            ->put('/admin/ajustes', ['redes' => ['facebook' => 'https://']])
            ->assertSessionHasErrors('redes.facebook');

        $this->assertNull(Ajuste::obtener('red_facebook'));
    }
}
