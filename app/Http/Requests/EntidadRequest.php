<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EntidadRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:80', 
            Rule::unique('entidades', 'nombre')->ignore($this->route('entidad')),],
            'descripcion' => ['required','string'],
            'direccion' => ['required','string', 'max:255'],
            'telefono' => ['required','string', 'max:50'],
            'whatsapp' => ['nullable','string', 'max:30'],
            'web_uri' => ['nullable','string', 'max:255'],
            'instagram_uri' => ['nullable','string', 'max:255'],
            'facebook_uri' => ['nullable','string', 'max:255'],
            'twitter_uri' => ['nullable','string', 'max:255'],
            'youtube_uri' => ['nullable','string', 'max:255'],
            'logo_uri' => ['nullable','string', 'max:255'],
            'email1' => ['nullable','string', 'max:50'],
            'email2' => ['nullable','string', 'max:50'],
            'entidad_principal' => [
                'nullable',
                'boolean',
                Rule::unique('entidades', 'entidad_principal')->where(function ($query) {
                    return $query->where('entidad_principal', true);
                })->ignore($this->route('entidad')),
            ],
        ];
    }

    public function messages():array {
        return [
            'nombre.required' => __('El nombre no puede quedar vacío'),
            'descripcion.required' => __('La descripción no puede quedar vacío'),
            'direccion.required' => __('La dirección no puede quedar vacío'),
            'telefono.required' => __('El telefono no puede quedar vacío'),
            'entidad_principal.unique' => __('Ya existe una entidad asignada como principal'),
            'nombre.unique' => __('Ya existe una entidad con ese nombre')
        ];
    }
}
