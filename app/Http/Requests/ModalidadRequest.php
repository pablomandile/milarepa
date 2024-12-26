<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModalidadRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:50', Rule::unique(table: 'modalidades', column: 'nombre')->ignore(id: request('modalidad'), idColumn: 'id')]
        ];
    }

    public function messages():array {
        return [
            'nombre.required' => __('El Nombre ya existe'),
            'nombre.unique' => __('La Modalidad ya existe')
        ];
    }
}
