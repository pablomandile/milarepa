<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InscripcionClaseRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255'],
            'nombre' => ['nullable', 'string', 'max:255'],
            'provincia_id' => ['nullable', 'integer', 'exists:provincias,id'],
            'municipio_id' => ['nullable', 'integer', 'exists:municipios,id'],
            'barrio_id' => ['nullable', 'integer', 'exists:barrios,id'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:100'],
            'registrar_datos' => ['nullable', 'boolean'],
            'existing_user_id' => ['nullable', 'integer', 'exists:users,id'],

            'clase_id' => ['required', 'integer', 'exists:clases,id'],
            'membresia' => ['required', 'string', 'max:255'],
            'pago' => ['required', 'in:Saldado,Pendiente,Parcial'],
            'online' => ['required', 'boolean'],
            'montoTharpa' => ['nullable', 'numeric', 'min:0'],
            'montoTienda' => ['nullable', 'numeric', 'min:0'],
            'articulos_tienda' => ['nullable', 'string'],
            'articulos_tharpa' => ['nullable', 'string'],
            'libros_tharpa_ids' => ['nullable', 'array'],
            'libros_tharpa_ids.*' => ['integer', 'exists:libros,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email no es válido.',
            'clase_id.required' => 'Debe seleccionar una clase.',
            'clase_id.exists' => 'La clase seleccionada no existe.',
            'membresia.required' => 'Debe seleccionar una membresía.',
            'pago.required' => 'Debe seleccionar un estado de pago.',
            'online.required' => 'Debe indicar si la inscripción es online.',
        ];
    }
}
