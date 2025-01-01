<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgramaRequest extends FormRequest
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
            'nombre' => ['required','string', 'max:255'],
            'programa' => ['required', 'string', 'max:5000']        ];
    }

    public function messages():array {
        return [
            'nombre.required' => __('El nombre no puede quedar vacío'),
            'programa.required' => __('El programa no puede quedar vacío')

        ];
    }
}
