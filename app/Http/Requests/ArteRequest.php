<?php

namespace App\Http\Requests;

use App\Models\Arte;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArteRequest extends FormRequest
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
            'tipo' => ['required', Rule::in(Arte::TIPOS)],
            'imagen_id' => ['nullable', 'integer', 'exists:imagenes,id'],
            'imagen' => ['nullable', 'image', 'max:4096'],
            'precio' => ['required', 'numeric', 'min:0'],
        ];
    }
}
