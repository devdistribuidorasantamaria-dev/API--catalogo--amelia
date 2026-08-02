<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SeccionRequest extends FormRequest
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
        $seccion = $this->route('seccion');

        return [
            'nombre' => [
                'required', 'string', 'max:120',
                Rule::unique('secciones', 'nombre')->ignore($seccion?->id),
            ],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function attributes(): array
    {
        return ['nombre' => 'nombre de la sección'];
    }
}
