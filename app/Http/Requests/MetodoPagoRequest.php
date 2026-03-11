<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MetodoPagoRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:30'],
            'descripcion' => ['required', 'string', 'max:100'],
            'tipo_de_pago' => ['required', Rule::in(['Online', 'Presencial'])],
            'imagen_id' => ['nullable', 'exists:imagenes,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => __('El nombre no puede quedar vacio'),
            'descripcion.required' => __('La descripcion no puede quedar vacio'),
            'tipo_de_pago.required' => __('Debe seleccionar un tipo de pago'),
            'tipo_de_pago.in' => __('El tipo de pago seleccionado no es valido'),
        ];
    }
}
