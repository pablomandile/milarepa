<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DevolucionAnexoRequest extends FormRequest
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
            'fecha' => ['required', 'date'],
            'devolvedor_id' => ['required', 'integer', 'exists:entidades,id', 'different:prestador_id'],
            'prestador_id' => ['required', 'integer', 'exists:entidades,id'],
            'libro_id' => ['required', 'integer', 'exists:libros,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'La fecha es obligatoria.',
            'devolvedor_id.required' => 'Debe seleccionar una entidad devolvedora.',
            'prestador_id.required' => 'Debe seleccionar una entidad prestadora.',
            'devolvedor_id.different' => 'La entidad devolvedora y prestadora deben ser distintas.',
            'libro_id.required' => 'Debe seleccionar un libro.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.min' => 'La cantidad debe ser mayor o igual a 1.',
        ];
    }
}
