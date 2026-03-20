<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LibroRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'isbn' => ['nullable', 'string', 'max:100'],
            'autor' => ['nullable', 'string', 'max:255'],
            'editorial' => ['nullable', 'string', 'max:255'],
            'imagen_id' => ['nullable', 'integer', 'exists:imagenes,id'],
            'precio' => ['required', 'numeric', 'min:0'],
        ];
    }
}
