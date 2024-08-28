<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DisponibilidadRequest extends FormRequest
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
            'descripcion' => ['required', 'string', 'max:30', Rule::unique(table: 'disponibilidades', column: 'descripcion')->ignore(id: request('disponibilidad'), idColumn: 'id')]
        ];
    }

    public function messages():array {
        return [
            'descripcion.unique' => __('La Disponibilidad ya existe')
        ];
    }
}
