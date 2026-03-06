<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LugarRequest extends FormRequest
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
                'max:200',
                Rule::unique('lugares', 'nombre')->ignore($this->route('lugar')),
            ],
            'descripcion' => ['required', 'string'],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'web_uri' => ['nullable', 'string', 'max:255'],
            'instagram_uri' => ['nullable', 'string', 'max:255'],
            'logo_uri' => ['nullable', 'string', 'max:255'],
            'email1' => ['nullable', 'string', 'max:50'],
            'email2' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => __('El nombre no puede quedar vacio'),
            'descripcion.required' => __('La descripcion no puede quedar vacia'),
            'direccion.required' => __('La direccion no puede quedar vacia'),
            'telefono.required' => __('El telefono no puede quedar vacio'),
            'nombre.unique' => __('Ya existe un lugar con ese nombre'),
        ];
    }
}
