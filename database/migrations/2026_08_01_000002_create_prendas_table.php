<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prendas', function (Blueprint $table) {
            $table->id();
            // Una prenda sin sección se muestra en el bloque inicial sin encabezado.
            $table->foreignId('seccion_id')->nullable()->constrained('secciones')->nullOnDelete();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->text('descripcion')->nullable();
            // Lista de tallas ("S", "M", "38"). jsonb permite consultarlas con operadores nativos.
            $table->jsonb('tallas')->default('[]');
            // Dos columnas para soportar precio único (solo desde) o rango (desde – hasta).
            $table->decimal('precio_desde', 10, 2)->nullable();
            $table->decimal('precio_hasta', 10, 2)->nullable();
            $table->boolean('activa')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->index(['seccion_id', 'orden']);
            $table->index('activa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prendas');
    }
};
