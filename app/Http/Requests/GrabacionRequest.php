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
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => __('El nombre no puede quedar vacío'),
        ];
    }
}
