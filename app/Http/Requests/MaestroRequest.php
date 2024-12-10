<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaestroRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:50'],
            'telefono' => ['nullable','string', 'max:50'],
            'email' => ['nullable','string', 'max:255']
        ];
    }

    public function messages():array {
        return [
            'nombre.required' => __('El nombre no puede quedar vacío')
        ];
    }
}
