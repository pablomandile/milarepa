<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BotonPagoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'link' => ['required', 'string', 'max:255'],
            'metodo_pago_id' => ['required', 'exists:metodos_pago,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => __('El nombre no puede quedar vacío'),
            'link.required' => __('El link no puede quedar vacío'),
            'metodo_pago_id.required' => __('El método de pago no puede quedar vacío'),
        ];
    }
}
