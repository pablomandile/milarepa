<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OracionCantadaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
            ],
            'descripcion' => ['required', 'string', 'max:5000'],
            'dia' => ['nullable', 'integer', 'min:1', 'max:31'],
            'dias_semana' => ['nullable', 'array'],
            'dias_semana.*' => ['string', Rule::in(['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'])],
            'hora' => ['required', 'date_format:H:i'],
            'periodicidad' => ['required', Rule::in(['Diaria', 'Mensual'])],
            'imagen' => ['nullable', 'string', 'max:2048'],
            'mostrar_en_calendario' => ['required', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $periodicidad = $this->input('periodicidad');
            $dia = $this->input('dia');
            $diasSemana = array_values(array_unique((array) $this->input('dias_semana', [])));

            if ($periodicidad === 'Mensual' && blank($dia)) {
                $validator->errors()->add('dia', 'El dia es obligatorio cuando la periodicidad es Mensual.');
            }

            if ($periodicidad === 'Diaria' && count($diasSemana) === 0) {
                $validator->errors()->add('dias_semana', 'Debe seleccionar al menos un dia de la semana para periodicidad diaria.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'nombre.required' => __('El nombre no puede quedar vacio'),
            'descripcion.required' => __('La descripcion no puede quedar vacia'),
            'dia.required' => __('El dia es obligatorio'),
            'dia.min' => __('El dia debe ser mayor o igual a 1'),
            'dia.max' => __('El dia debe ser menor o igual a 31'),
            'hora.required' => __('La hora es obligatoria'),
            'hora.date_format' => __('La hora debe tener formato hh:mm'),
            'periodicidad.required' => __('La periodicidad es obligatoria'),
            'periodicidad.in' => __('La periodicidad debe ser Diaria o Mensual'),
            'dias_semana.array' => __('Los dias de la semana son invalidos'),
        ];
    }
}
