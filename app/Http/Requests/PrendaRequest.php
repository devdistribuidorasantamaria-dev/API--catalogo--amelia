<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'seccion_id' => ['nullable', 'integer', 'exists:secciones,id'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            // Se recibe como texto "S, M, L" y se normaliza en prepareForValidation.
            'tallas' => ['nullable', 'array'],
            'tallas.*' => ['string', 'max:20'],
            'precio_desde' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'precio_hasta' => ['nullable', 'numeric', 'min:0', 'max:999999.99', 'gte:precio_desde'],
            'activa' => ['boolean'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],

            'fotos' => ['nullable', 'array', 'max:12'],
            'fotos.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],

            // IDs de imágenes existentes: las que se borran y el orden final.
            'eliminar_imagenes' => ['nullable', 'array'],
            'eliminar_imagenes.*' => ['integer'],
            'orden_imagenes' => ['nullable', 'array'],
            'orden_imagenes.*' => ['integer'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $tallas = $this->input('tallas');

        if (is_string($tallas)) {
            $tallas = collect(explode(',', $tallas))
                ->map(fn ($t) => trim($t))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $this->merge([
            'tallas' => $tallas ?? [],
            'activa' => $this->boolean('activa'),
            'seccion_id' => $this->input('seccion_id') ?: null,
            'precio_desde' => $this->filled('precio_desde') ? $this->input('precio_desde') : null,
            'precio_hasta' => $this->filled('precio_hasta') ? $this->input('precio_hasta') : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'precio_hasta.gte' => 'El precio máximo del rango no puede ser menor que el mínimo.',
            'fotos.*.image' => 'Cada archivo debe ser una imagen (JPG, PNG o WEBP).',
            'fotos.*.max' => 'Cada foto debe pesar máximo 8 MB.',
        ];
    }
}
