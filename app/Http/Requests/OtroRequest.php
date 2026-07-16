<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OtroRequest extends FormRequest
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
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'tipo' => ['nullable', 'string', 'max:60'],
            'imagen_id' => ['nullable', 'integer', 'exists:imagenes,id'],
            'imagen' => ['nullable', 'image', 'max:4096'],
            'precio' => ['required', 'numeric', 'min:0'],
        ];
    }
}
