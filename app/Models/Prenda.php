<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Prenda extends Model
{
    use HasFactory;

    protected $fillable = [
        'seccion_id', 'nombre', 'slug', 'descripcion',
        'tallas', 'precio_desde', 'precio_hasta', 'activa', 'orden',
    ];

    protected $casts = [
        'tallas' => 'array',
        'precio_desde' => 'decimal:2',
        'precio_hasta' => 'decimal:2',
        'activa' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $prenda) {
            if (blank($prenda->slug)) {
                $prenda->slug = static::slugUnico($prenda->nombre, $prenda->id);
            }
        });
    }

    public static function slugUnico(string $nombre, ?int $ignorarId = null): string
    {
        $base = Str::slug($nombre) ?: 'prenda';
        $slug = $base;
        $n = 2;

        while (static::where('slug', $slug)->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))->exists()) {
            $slug = "{$base}-{$n}";
            $n++;
        }

        return $slug;
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class, 'seccion_id');
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(PrendaImagen::class, 'prenda_id')->orderBy('orden');
    }

    public function portada(): HasOne
    {
        return $this->hasOne(PrendaImagen::class, 'prenda_id')->orderBy('orden');
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderBy('orden')->orderBy('id');
    }

    /**
     * Texto de precio tal como se muestra en el catálogo: "$25.00" o "$25.00 – $30.00".
     */
    public function precioTexto(): string
    {
        if ($this->precio_desde === null) {
            return '—';
        }

        $desde = '$'.number_format((float) $this->precio_desde, 2);

        if ($this->precio_hasta === null || (float) $this->precio_hasta <= (float) $this->precio_desde) {
            return $desde;
        }

        return $desde.' – $'.number_format((float) $this->precio_hasta, 2);
    }
}
