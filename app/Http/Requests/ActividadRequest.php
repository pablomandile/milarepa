<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;


class ActividadRequest extends FormRequest
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
            'tipo_actividad_id' => ['required', 'exists:tipos_actividad,id'],
            'nombre' => ['required', 'string', 'max:80'],
            'descripcion_id' => ['nullable', 'exists:descripciones,id'],
            'observaciones' => ['nullable','string', 'max:255'],
            'imagen_id' => ['nullable', 'exists:imagenes,id'],
            'fecha_inicio' => ['required','date'],
            'fecha_fin' => ['required','date'],
            'pagoAmticipado' => ['nullable','date'],
            'entidad_id' => ['required', 'exists:entidades,id'],
            'disponibilidad_id' => ['nullable','exists:disponibilidades,id'],
            'modalidad_id' => ['required', 'exists:modalidades,id'],
            'esquema_precio_id' => ['required', 'exists:esquema_precios,id'],
            'esquema_descuento_id' => ['nullable','exists:esquema_descuentos,id'],
            'link_web' => ['nullable','string'],
            'stream_id' => ['nullable','exists:streams,id'],
            'grabacion_id' => ['nullable','exists:grabaciones,id'],
            'programa_id' => ['nullable','exists:programas,id'],
            'estado' => ['nullable', 'boolean'],
            'metodos_pago_ids' => ['nullable', 'array'],
            'metodos_pago_ids.*' => ['exists:metodos_pago,id'],
            'hospedajes_ids' => ['nullable', 'array'],
            'hospedajes_ids.*' => ['exists:hospedajes,id'],
            'comidas_ids' => ['nullable', 'array'],
            'comidas_ids.*' => ['exists:comidas,id'],
            'transportes_ids' => ['nullable', 'array'],
            'transportes_ids.*' => ['exists:transportes,id'],
            'maestros_ids' => ['nullable', 'array'],
            'maestros_ids.*' => ['exists:maestros,id'],
            'coordinadores_ids' => ['nullable', 'array'],
            'coordinadores_ids.*' => ['exists:coordinadores,id'],
        ];
    }

    public function messages():array {
        return [
            'nombre.required' => __('El nombre no puede quedar vacío'),
            'descripcion.required' => __('La descripción no puede quedar vacía'),
            'tipo_actividad_id.required' => __('El tipo no puede quedar vacío'),
            'imagen_id.required' => __('La imagen no puede quedar vacía'),
            'fecha_inicio.required' => __('La fecha de inicio no puede quedar vacía'),
            'entidad_id.required' => __('La entidad de inicio no puede quedar vacía'),
            'modalidad_id.required' => __('La modalidad de inicio no puede quedar vacía'),
            'esquema_precio_id.required' => __('El esquema de precio no puede quedar vacío'),
        ];
    }

    public function prepareForValidation()
    {
        if ($this->has('fecha_inicio')) {
            $this->merge([
                'fecha_inicio' => \Carbon\Carbon::parse($this->fecha)->format('Y-m-d'),
            ]);
        }
        if ($this->has('fecha_fin')) {
            $this->merge([
                'fecha_fin' => \Carbon\Carbon::parse($this->fecha)->format('Y-m-d'),
            ]);
        }
        if ($this->has('pagoAmticipado')) {
            $this->merge([
                'pagoAmticipado' => \Carbon\Carbon::parse($this->fecha)->format('Y-m-d'),
            ]);
        }
        // Ejemplo de imprimir un log con todo el request:
        Log::info('Datos originales del request:', $this->all());

    }
}
