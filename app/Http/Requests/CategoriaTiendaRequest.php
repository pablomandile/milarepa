<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoriaTiendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $categoria = $this->route('categoria_tienda');

        return [
            'nombre' => [
                'required',
                'string',
                'max:120',
                Rule::unique('categorias_tienda', 'nombre')->ignore($categoria?->id),
            ],
            'orden' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
