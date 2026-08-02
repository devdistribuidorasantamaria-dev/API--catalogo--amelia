<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PrendaImagen extends Model
{
    protected $table = 'prenda_imagenes';

    protected $fillable = ['prenda_id', 'ruta', 'orden'];

    public function prenda(): BelongsTo
    {
        return $this->belongsTo(Prenda::class, 'prenda_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->ruta);
    }
}
