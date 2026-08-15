<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ConPreciosPorMoneda;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComidaRequest extends FormRequest
{
    use ConPreciosPorMoneda;

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
            'descripcion' => ['required','string', 'max:255'],
            'botonpago_id' => ['nullable', 'exists:botones_pago,id'],
            'precio' => ['required', 'numeric', 'min:0'],
            'vegano' => ['nullable', 'boolean'],
            'celiaco' => ['nullable', 'boolean']
        ] + $this->reglasPrecios();
    }

    public function messages():array {
        return [
            'nombre.required' => __('El nombre no puede quedar vacío'),
            'descripcion.required' => __('La descripción no puede quedar vacía'),
            'precio.required' => __('El precio no puede quedar vacío'),
            'precio.numeric' => __('El precio debe ser numérico'),
            'precio.min' => __('El precio no puede ser negativo'),
        ] + $this->mensajesPrecios();
    }
}
