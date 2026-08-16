<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_analitica', function (Blueprint $table) {
            $table->id();
            // visita | agregar | consultar (App\Enums\TipoEvento).
            $table->string('tipo', 12);
            // Nulo en las visitas. Al borrar la prenda el evento se conserva sin ella:
            // el histórico de un mes no debería encogerse por limpiar el catálogo.
            $table->foreignId('prenda_id')->nullable()->constrained('prendas')->nullOnDelete();
            // HMAC-SHA256 de ip+user-agent+fecha con la APP_KEY: irreversible y con sal
            // diaria, así que no se puede volver a la IP ni seguir a nadie entre días.
            // Sólo sirve para separar visitas únicas de recargas dentro del mismo día.
            $table->string('visitante_hash', 64)->nullable();
            $table->timestamp('creado_en')->useCurrent();

            // Series por día del panel.
            $table->index(['tipo', 'creado_en']);
            // Ranking de prendas más seleccionadas.
            $table->index(['prenda_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_analitica');
    }
};
