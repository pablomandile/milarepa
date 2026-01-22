<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
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
            'accesibilidad' => ['nullable', 'boolean'],
            'accesibilidad_desc' => ['nullable','string'],
            'direccion' => ['nullable','string', 'max:255'],
            'pais_id' => ['required', 'exists:paises,id'],
            'provincia_id' => ['required', 'exists:provincias,id'],
            'municipio_id' => ['nullable', 'exists:municipios,id'],
            'barrio_id' => ['nullable', 'exists:barrios,id'],
            'telefono' => ['required','string', 'max:100'],
            'whatsapp' => ['nullable','string', 'max:100'],
            'fecha_nacimiento' => ['nullable','date'],

            'sexo_id' => ['required', 'exists:sexos,id'],
            'membresia_id' => ['required', 'exists:membresias,id'],
            'es_maestro' => ['nullable', 'boolean'],
            'es_coordinador' => ['nullable', 'boolean'],
            'perfil_completo' => ['nullable', 'boolean'],
            'msgxmail' => ['nullable', 'boolean'],
            'msgxwapp' => ['nullable', 'boolean']
        ];
    }

    public function messages():array {
        return [
            'pais_id.required' => __('El país no puede quedar vacío'),
            'provincia_id.required' => __('La provincia no puede quedar vacía'),
            'municipio_id.exists' => __('Seleccione un municipio válido'),
            'barrio_id.exists' => __('Seleccione un barrio válido'),
            'telefono.required' => __('El telefono no puede quedar vacío'),
            'sexo_id.required' => __('El sexo no puede quedar vacío'),
            'membresia_id.required' => __('La membresía no puede quedar vacía')
        ];
    }
}
