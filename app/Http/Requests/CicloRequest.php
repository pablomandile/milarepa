<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CicloRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('ciclos', 'nombre')->ignore(request('ciclo'), 'id'),
            ],
            'mes' => ['required', 'integer', 'between:1,12'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => __('El nombre no puede quedar vacio'),
            'nombre.unique' => __('El ciclo ya existe'),
            'mes.required' => __('Debes seleccionar un mes'),
            'mes.between' => __('El mes seleccionado no es valido'),
        ];
    }
}
