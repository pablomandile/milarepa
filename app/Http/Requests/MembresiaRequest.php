<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MembresiaRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:20'],
            'descripcion' => ['nullable', 'string', 'max:150'],
            'entidad_id' => ['required', 'exists:entidades,id'],
            'botonpago_id' => ['nullable', 'exists:botones_pago,id'],
            'valor' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages():array {
        return [
            'nombre.required' => __('El nombre no puede quedar vacío'),

        ];
    }
}
