<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransporteRequest extends FormRequest
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
            'descripcion' => ['required','string', 'max:255'],
            'botonpago_id' => ['nullable', 'exists:botones_pago,id'],
            'precio' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages():array {
        return [
            'descripcion.required' => __('La descripción no puede quedar vacío'),
            'precio.required' => __('El precio no puede quedar vacío')
        ];
    }
}
