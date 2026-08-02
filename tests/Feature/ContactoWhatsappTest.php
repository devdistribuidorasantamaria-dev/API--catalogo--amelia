<?php

namespace Tests\Feature;

use App\Models\Ajuste;
use App\Models\User;
use App\Services\ContactoWhatsapp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactoWhatsappTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_numero_la_api_no_expone_enlace(): void
    {
        $this->getJson('/api/catalogo')
            ->assertOk()
            ->assertJsonPath('whatsapp_url', null)
            ->assertJsonPath('whatsapp_numero', null);
    }

    public function test_la_api_expone_el_numero_suelto_para_armar_mensajes(): void
    {
        Ajuste::guardar('whatsapp_numero', '593987654321');

        $this->getJson('/api/catalogo')
            ->assertOk()
            ->assertJsonPath('whatsapp_numero', '593987654321');
    }

    public function test_la_api_arma_el_enlace_con_el_mensaje_precargado(): void
    {
        Ajuste::guardar('whatsapp_numero', '593987654321');
        Ajuste::guardar('whatsapp_mensaje', 'Hola, ¿tienen la talla M?');

        $this->getJson('/api/catalogo')
            ->assertOk()
            ->assertJsonPath(
                'whatsapp_url',
                'https://wa.me/593987654321?text=Hola%2C%20%C2%BFtienen%20la%20talla%20M%3F'
            );
    }

    public function test_usa_el_mensaje_por_defecto_si_no_se_configura(): void
    {
        Ajuste::guardar('whatsapp_numero', '593987654321');

        $url = $this->getJson('/api/catalogo')->json('whatsapp_url');

        $this->assertStringContainsString(rawurlencode(ContactoWhatsapp::MENSAJE_POR_DEFECTO), $url);
    }

    public function test_el_panel_limpia_el_numero_al_guardar(): void
    {
        $this->actingAs(User::factory()->create())
            ->put('/admin/ajustes', [
                'subtitulo' => 'Colección',
                'whatsapp_numero' => '+593 98 765 4321',
                'whatsapp_mensaje' => 'Hola',
            ])
            ->assertRedirect('/admin/ajustes');

        $this->assertSame('593987654321', Ajuste::obtener('whatsapp_numero'));
    }

    public function test_rechaza_un_numero_demasiado_corto(): void
    {
        $this->actingAs(User::factory()->create())
            ->put('/admin/ajustes', ['whatsapp_numero' => '0987'])
            ->assertSessionHasErrors('whatsapp_numero');

        $this->assertNull(Ajuste::obtener('whatsapp_numero'));
    }

    public function test_vaciar_el_numero_esconde_el_boton(): void
    {
        Ajuste::guardar('whatsapp_numero', '593987654321');

        $this->actingAs(User::factory()->create())
            ->put('/admin/ajustes', ['whatsapp_numero' => ''])
            ->assertRedirect('/admin/ajustes');

        $this->assertNull(Ajuste::obtener('whatsapp_numero'));
        $this->getJson('/api/catalogo')->assertJsonPath('whatsapp_url', null);
    }
}
