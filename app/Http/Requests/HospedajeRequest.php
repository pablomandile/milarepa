<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HospedajeRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable','string', 'max:255'],
            'botonpago_id' => ['nullable', 'exists:botones_pago,id'],
            'precio' => ['required', 'numeric', 'min:0'],
            'lugar_hospedaje_id' => ['required', 'exists:lugares_hospedaje,id'], 
        ];
    }

    public function messages():array {
        return [
            'nombre.required' => __('El nombre no puede quedar vacío'),
            'precio.required' => __('El precio no puede quedar vacío')
        ];
    }
}
