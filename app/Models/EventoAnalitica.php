<?php

namespace App\Models;

use App\Enums\TipoEvento;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un evento anónimo del catálogo público. Sólo se inserta y se lee agregado:
 * nunca se actualiza, de ahí que no tenga updated_at.
 */
class EventoAnalitica extends Model
{
    // Eloquent deduciría 'evento_analiticas'.
    protected $table = 'eventos_analitica';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = null;

    protected $fillable = ['tipo', 'prenda_id', 'visitante_hash', 'creado_en'];

    protected $casts = [
        'tipo' => TipoEvento::class,
        'creado_en' => 'datetime',
    ];

    public function prenda(): BelongsTo
    {
        return $this->belongsTo(Prenda::class, 'prenda_id');
    }

    public function scopeDelTipo(Builder $query, TipoEvento $tipo): Builder
    {
        return $query->where('tipo', $tipo->value);
    }

    /** Agregar + consultar: el interés por una prenda concreta. */
    public function scopeSelecciones(Builder $query): Builder
    {
        return $query->whereIn('tipo', TipoEvento::selecciones());
    }

    public function scopeDesde(Builder $query, CarbonInterface $desde): Builder
    {
        return $query->where('creado_en', '>=', $desde);
    }
}
