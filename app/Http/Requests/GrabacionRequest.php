<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GrabacionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:50'],
            'botonpago_id' => ['nullable', 'exists:botones_pago,id'],
            'valor' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => __('El nombre no puede quedar vacío'),
            'valor.required' => __('El valor no puede quedar vacío'),
            'valor.numeric' => __('El valor debe ser numérico'),
            'valor.min' => __('El valor no puede ser negativo'),
        ];
    }
}




