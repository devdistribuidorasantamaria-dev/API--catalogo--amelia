<?php

namespace Tests\Feature;

use App\Models\EventoAnalitica;
use App\Models\Prenda;
use App\Services\RegistroAnalitica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AnaliticaApiTest extends TestCase
{
    use RefreshDatabase;

    /** User-agent de navegador real: el filtro de bots no debe tocarlo. */
    private const UA_NAVEGADOR = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
        .'(KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    /**
     * @param  array<string, mixed>  $datos
     */
    private function enviar(array $datos, string $userAgent = self::UA_NAVEGADOR)
    {
        return $this->withHeader('User-Agent', $userAgent)->postJson('/api/eventos', $datos);
    }

    public function test_registra_una_visita_sin_prenda(): void
    {
        $this->enviar(['tipo' => 'visita'])->assertNoContent();

        $evento = EventoAnalitica::sole();

        $this->assertSame('visita', $evento->tipo->value);
        $this->assertNull($evento->prenda_id);
        // La huella se guarda, pero como digest: ni la IP ni el user-agent en claro.
        $this->assertSame(64, strlen($evento->visitante_hash));
        $this->assertStringNotContainsString('127.0.0.1', $evento->visitante_hash);
    }

    public function test_registra_agregar_y_consultar_como_tipos_distintos(): void
    {
        $prenda = Prenda::create(['nombre' => 'Vestido midi', 'tallas' => ['M'], 'precio_desde' => 38]);

        $this->enviar(['tipo' => 'agregar', 'prenda_id' => $prenda->id])->assertNoContent();
        $this->enviar(['tipo' => 'consultar', 'prenda_id' => $prenda->id])->assertNoContent();

        $this->assertSame(1, EventoAnalitica::where('tipo', 'agregar')->count());
        $this->assertSame(1, EventoAnalitica::where('tipo', 'consultar')->count());
        $this->assertSame(2, EventoAnalitica::selecciones()->count());
    }

    public function test_rechaza_un_tipo_de_evento_desconocido(): void
    {
        $this->enviar(['tipo' => 'compra'])->assertJsonValidationErrorFor('tipo');

        $this->assertSame(0, EventoAnalitica::count());
    }

    public function test_exige_la_prenda_en_agregar_y_consultar(): void
    {
        $this->enviar(['tipo' => 'agregar'])->assertJsonValidationErrorFor('prenda_id');
        $this->enviar(['tipo' => 'consultar'])->assertJsonValidationErrorFor('prenda_id');

        $this->assertSame(0, EventoAnalitica::count());
    }

    public function test_rechaza_una_prenda_inexistente(): void
    {
        $this->enviar(['tipo' => 'agregar', 'prenda_id' => 9999])->assertJsonValidationErrorFor('prenda_id');

        $this->assertSame(0, EventoAnalitica::count());
    }

    public function test_rechaza_la_visita_que_trae_prenda(): void
    {
        $prenda = Prenda::create(['nombre' => 'Blusa seda', 'tallas' => [], 'precio_desde' => 22]);

        $this->enviar(['tipo' => 'visita', 'prenda_id' => $prenda->id])->assertJsonValidationErrorFor('prenda_id');

        $this->assertSame(0, EventoAnalitica::count());
    }

    public function test_descarta_los_bots_pero_responde_igual(): void
    {
        $this->enviar(['tipo' => 'visita'], 'Googlebot/2.1 (+http://www.google.com/bot.html)')->assertNoContent();
        $this->enviar(['tipo' => 'visita'], 'curl/8.5.0')->assertNoContent();
        $this->enviar(['tipo' => 'visita'], '')->assertNoContent();

        $this->assertSame(0, EventoAnalitica::count());
    }

    public function test_el_filtro_de_bots_deja_pasar_a_los_navegadores_de_verdad(): void
    {
        $filtro = app(RegistroAnalitica::class);

        $navegadores = [
            self::UA_NAVEGADOR,
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (X11; Linux x86_64; rv:122.0) Gecko/20100101 Firefox/122.0',
            // Navegador embebido de Android, con el que se abre un enlace de WhatsApp.
            'Mozilla/5.0 (Linux; Android 13; SM-A536E) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/120.0.0.0 Mobile Safari/537.36',
        ];

        foreach ($navegadores as $ua) {
            $this->assertFalse($filtro->esBot($ua), "Se descartó un navegador real: {$ua}");
        }

        // El que sí es bot: el previsualizador de enlaces de WhatsApp.
        $this->assertTrue($filtro->esBot('WhatsApp/2.23.20.0 A'));
    }

    public function test_la_huella_cambia_cada_dia(): void
    {
        $filtro = app(RegistroAnalitica::class);
        $peticion = Request::create('/api/eventos', 'POST', server: [
            'REMOTE_ADDR' => '190.15.1.1',
            'HTTP_USER_AGENT' => self::UA_NAVEGADOR,
        ]);

        $this->travelTo(now()->startOfDay());
        $hoy = $filtro->huella($peticion);

        $this->travel(1)->days();
        $manana = $filtro->huella($peticion);

        $this->assertNotSame($hoy, $manana, 'La sal diaria debe romper la correlación entre días.');
    }
}
