<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgramaEstudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $programaEstudio = $this->route('programaEstudio');
        $id = is_object($programaEstudio) ? ($programaEstudio->id ?? null) : $programaEstudio;

        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('programa_estudios', 'nombre')->ignore($id)],
            'abreviacion' => ['nullable', 'string', 'max:10', Rule::unique('programa_estudios', 'abreviacion')->ignore($id)],
            'descripcion' => ['nullable', 'string'],
            'compromisos_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'compromisos_pdf_borrar' => ['nullable', 'boolean'],
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

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.unique' => 'Ya existe un programa de estudio con ese nombre',
            'abreviacion.unique' => 'Ya existe un programa de estudio con esa abreviación',
        ];
    }
}
