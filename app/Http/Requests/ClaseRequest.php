<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'ciclo_id' => ['required', 'exists:ciclos,id'],
            'entidad_id' => ['required', 'exists:entidades,id'],
            'mes_referencia' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'imagen_id' => ['nullable', 'exists:imagenes,id'],
            'dias_semana' => ['required', 'array', 'min:1'],
            'dias_semana.*' => ['string', Rule::in(['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'])],
            'titulos_por_fecha' => ['nullable', 'array'],
            'titulos_por_fecha.*' => ['nullable', 'string', 'max:255'],
            'horario_desde' => ['required', 'date_format:H:i'],
            'horario_hasta' => ['required', 'date_format:H:i', 'after:horario_desde'],
            'maestro_id' => ['nullable', 'exists:maestros,id'],
            'coordinador_id' => ['nullable', 'exists:coordinadores,id'],
            'esquema_precio_id' => ['nullable', 'exists:esquema_precios,id'],
            'mostrar_en_calendario' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => __('El nombre no puede quedar vacio'),
            'ciclo_id.required' => __('Debe seleccionar un ciclo'),
            'entidad_id.required' => __('Debe seleccionar una entidad'),
            'mes_referencia.required' => __('Debe seleccionar el mes de la clase'),
            'mes_referencia.regex' => __('El mes debe tener formato YYYY-MM'),
            'dias_semana.required' => __('Debe seleccionar al menos un dia de la semana'),
            'dias_semana.min' => __('Debe seleccionar al menos un dia de la semana'),
            'horario_desde.required' => __('Debe ingresar el horario desde'),
            'horario_hasta.required' => __('Debe ingresar el horario hasta'),
            'horario_desde.date_format' => __('El horario desde debe tener formato hh:mm'),
            'horario_hasta.date_format' => __('El horario hasta debe tener formato hh:mm'),
            'horario_hasta.after' => __('El horario hasta debe ser posterior al horario desde'),
            'mostrar_en_calendario.required' => __('Debe indicar si se muestra en el calendario'),
        ];
    }
}
