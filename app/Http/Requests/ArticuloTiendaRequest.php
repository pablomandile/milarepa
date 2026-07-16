<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArticuloTiendaRequest extends FormRequest
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
            'categoria_tienda_id' => ['required', 'integer', 'exists:categorias_tienda,id'],
            'imagen_id' => ['nullable', 'integer', 'exists:imagenes,id'],
            'imagen' => ['nullable', 'image', 'max:4096'],
            'precio' => ['required', 'numeric', 'min:0'],
        ];
    }
}
