<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prenda_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prenda_id')->constrained('prendas')->cascadeOnDelete();
            // Ruta relativa dentro del disco 'public' (ej. prendas/abc123.jpg).
            $table->string('ruta');
            // orden = 0 es la portada.
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->index(['prenda_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prenda_imagenes');
    }
};
