<?php

namespace App\Http\Requests\Api;

use App\Enums\TipoEvento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarEventoRequest extends FormRequest
{
    /** Endpoint público: lo llama el catálogo, que no tiene sesión. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::enum(TipoEvento::class)],
            'prenda_id' => [
                'nullable',
                'integer',
                // Agregar y consultar son siempre sobre una prenda; la visita, nunca.
                Rule::requiredIf(fn () => in_array($this->input('tipo'), TipoEvento::selecciones(), true)),
                Rule::prohibitedIf(fn () => $this->input('tipo') === TipoEvento::Visita->value),
                'exists:prendas,id',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tipo' => 'tipo de evento',
            'prenda_id' => 'prenda',
        ];
    }

    public function tipo(): TipoEvento
    {
        return TipoEvento::from((string) $this->input('tipo'));
    }

    public function prendaId(): ?int
    {
        $id = $this->input('prenda_id');

        return $id === null ? null : (int) $id;
    }
}
