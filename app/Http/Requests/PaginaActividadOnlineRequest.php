<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaginaActividadOnlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mes_referencia' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'imagen_id' => ['nullable', 'exists:imagenes,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'mes_referencia.required' => __('Debe seleccionar un mes'),
            'mes_referencia.regex' => __('El mes debe tener formato YYYY-MM'),
        ];
    }
}

