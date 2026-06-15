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
        $membresiaId = $this->route('membresia');
        if (is_object($membresiaId)) {
            $membresiaId = $membresiaId->id ?? null;
        }

        return [
            'nombre' => ['required', 'string', 'max:20'],
            'abreviacion' => ['nullable', 'string', 'max:10', Rule::unique('membresias', 'abreviacion')->ignore($membresiaId)->whereNull('deleted_at')],
            'descripcion' => ['nullable', 'string', 'max:150'],
            'info' => ['nullable', 'string'],
            'entidad_id' => ['required', 'exists:entidades,id'],
            'botonpago_id' => ['nullable', 'exists:botones_pago,id'],
            'imagen_id' => ['nullable', 'exists:imagenes,id'],
            'imagen' => ['nullable', 'image', 'max:4096'],
            'valor' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('abreviacion')) {
            $abreviacion = trim((string) $this->input('abreviacion'));
            $this->merge([
                'abreviacion' => $abreviacion === '' ? null : strtoupper($abreviacion),
            ]);
        }
    }

    public function messages():array {
        return [
            'nombre.required' => __('El nombre no puede quedar vacío'),

        ];
    }
}
