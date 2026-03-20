<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventarioLibroRequest extends FormRequest
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
        $inventario = $this->route('inventario_libro');

        return [
            'libro_id' => [
                'required',
                'integer',
                'exists:libros,id',
                Rule::unique('inventario_libros', 'libro_id')->ignore($inventario?->id),
            ],
            'cantidad' => ['required', 'integer', 'min:0'],
        ];
    }
}
