<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LugarHospedajeRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable','string', 'max:255'],
            'direccion' => ['required','string', 'max:255'],
            'telefono' => ['nullable','string', 'max:50'],
            'email' => ['nullable','string', 'max:50'],
            'web' => ['nullable','string', 'max:255'],
        ];
    }

    public function messages():array {
        return [
            'nombre.required' => __('El nombre no puede quedar vacío'),
            'direccion.required' => __('La dirección no puede quedar vacía')
        ];
    }
}
