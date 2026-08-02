<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Seccion extends Model
{
    use HasFactory;

    // "secciones" no es el plural que Eloquent deduce de Seccion.
    protected $table = 'secciones';

    protected $fillable = ['nombre', 'slug', 'orden'];

    protected static function booted(): void
    {
        static::saving(function (self $seccion) {
            if (blank($seccion->slug)) {
                $seccion->slug = static::slugUnico($seccion->nombre, $seccion->id);
            }
        });
    }

    public static function slugUnico(string $nombre, ?int $ignorarId = null): string
    {
        $base = Str::slug($nombre) ?: 'seccion';
        $slug = $base;
        $n = 2;

        while (static::where('slug', $slug)->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))->exists()) {
            $slug = "{$base}-{$n}";
            $n++;
        }

        return $slug;
    }

    public function prendas(): HasMany
    {
        return $this->hasMany(Prenda::class, 'seccion_id');
    }

    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }
}
