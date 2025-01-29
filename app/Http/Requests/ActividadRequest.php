<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'tipo_actividad_id' => ['required', 'exists:tipos_actividad, id'],
            'nombre' => ['required', 'string', 'max:80'],
            'descripcion_id' => ['required', 'exists:descripciones, id'],
            'observaciones' => ['string', 'max:255'],
            'imagen_id' => ['required', 'exists:imagenes, id'],
            'fecha_inicio' => ['required','date'],
            'fecha_fin' => ['required','date'],
            'pagoAmticipado' => ['date'],
            'entidad_id' => ['required', 'exists:entidades, id'],
            'disponibilidad_id' => ['exists:disponibilidades, id'],
            'modalidad_id' => ['required', 'exists:modalidades, id'],
            'esquema_precio_id' => ['required', 'exists:esquema_precios, id'],
            'esquema_descuento_id' => ['exists:esquema_descuentos, id'],
            'link_grabacion' => ['string'],
            'link_web' => ['string'],
            'stream_id' => ['exists:streams, id'],
            'programa_id' => ['exists:programas, id'],
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
}
