<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermisoRequest extends FormRequest
{
    public function rules(): array
    {
        $permisoId = $this->route('permiso')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')->ignore($permisoId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('El nombre no puede quedar vacío'),
            'name.unique' => __('El permiso ya existe'),
        ];
    }
}
