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

    /**
     * Descarta los horarios por dia vacios antes de validar: un input de hora sin
     * completar llega como "" y "nullable" no lo exime de date_format. Sin horario
     * propio, el dia usa la hora general (no es un error de validacion).
     */
    protected function prepareForValidation(): void
    {
        $limpiarHorarios = fn ($horarios) => is_array($horarios)
            ? array_filter($horarios, fn ($hora) => is_string($hora) && $hora !== '')
            : $horarios;

        $merge = [];

        if ($this->has('horarios_por_dia')) {
            $merge['horarios_por_dia'] = $limpiarHorarios($this->input('horarios_por_dia'));
        }

        if (is_array($this->input('configuracion_por_mes'))) {
            $merge['configuracion_por_mes'] = collect($this->input('configuracion_por_mes'))
                ->map(function ($configuracion) use ($limpiarHorarios) {
                    if (is_array($configuracion) && array_key_exists('horarios_por_dia', $configuracion)) {
                        $configuracion['horarios_por_dia'] = $limpiarHorarios($configuracion['horarios_por_dia']);
                    }

                    return $configuracion;
                })
                ->all();
        }

        // Una hora o un mensaje sin completar llegan como "": los pasamos a null para
        // que "nullable" los exima de date_format/string y no sean falsos errores.
        if (is_array($this->input('excepciones_por_fecha'))) {
            $merge['excepciones_por_fecha'] = collect($this->input('excepciones_por_fecha'))
                ->map(function ($excepcion) {
                    if (!is_array($excepcion)) {
                        return $excepcion;
                    }

                    if (array_key_exists('hora', $excepcion) && !(is_string($excepcion['hora']) && $excepcion['hora'] !== '')) {
                        $excepcion['hora'] = null;
                    }

                    if (array_key_exists('mensaje', $excepcion)) {
                        $mensaje = is_string($excepcion['mensaje']) ? trim($excepcion['mensaje']) : null;
                        $excepcion['mensaje'] = $mensaje === '' ? null : $mensaje;
                    }

                    return $excepcion;
                })
                ->all();
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
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
            'horarios_por_dia' => ['nullable', 'array'],
            'horarios_por_dia.*' => ['nullable', 'date_format:H:i'],
            'periodicidad' => ['required', Rule::in(['Diaria', 'Mensual'])],
            'configuracion_por_mes' => ['nullable', 'array'],
            'configuracion_por_mes.*.mes' => ['required', 'integer', 'min:1', 'max:12', 'distinct'],
            'configuracion_por_mes.*.periodicidad' => ['required', Rule::in(['Diaria', 'Mensual'])],
            'configuracion_por_mes.*.dia' => ['nullable', 'integer', 'min:1', 'max:31'],
            'configuracion_por_mes.*.dias_semana' => ['nullable', 'array'],
            'configuracion_por_mes.*.dias_semana.*' => ['string', Rule::in(['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'])],
            'configuracion_por_mes.*.hora' => ['required', 'date_format:H:i'],
            'configuracion_por_mes.*.horarios_por_dia' => ['nullable', 'array'],
            'configuracion_por_mes.*.horarios_por_dia.*' => ['nullable', 'date_format:H:i'],
            'excepciones_por_fecha' => ['nullable', 'array'],
            'excepciones_por_fecha.*.fecha' => ['required', 'date_format:Y-m-d', 'distinct'],
            'excepciones_por_fecha.*.hora' => ['nullable', 'date_format:H:i'],
            'excepciones_por_fecha.*.mensaje' => ['nullable', 'string', 'max:255'],
            'modalidad_id' => ['nullable', 'exists:modalidades,id'],
            'stream_id' => ['nullable', 'exists:streams,id'],
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

            foreach ((array) $this->input('configuracion_por_mes', []) as $index => $configuracion) {
                $configPeriodicidad = $configuracion['periodicidad'] ?? null;
                $configDia = $configuracion['dia'] ?? null;
                $configDiasSemana = array_values(array_unique((array) ($configuracion['dias_semana'] ?? [])));

                if ($configPeriodicidad === 'Mensual' && blank($configDia)) {
                    $validator->errors()->add("configuracion_por_mes.$index.dia", 'El dia es obligatorio cuando la configuracion mensual es Mensual.');
                }

                if ($configPeriodicidad === 'Diaria' && count($configDiasSemana) === 0) {
                    $validator->errors()->add("configuracion_por_mes.$index.dias_semana", 'Debe seleccionar al menos un dia de la semana para la configuracion mensual diaria.');
                }
            }

            foreach ((array) $this->input('excepciones_por_fecha', []) as $index => $excepcion) {
                $tieneHora = is_string($excepcion['hora'] ?? null) && ($excepcion['hora'] ?? '') !== '';
                $tieneMensaje = is_string($excepcion['mensaje'] ?? null) && trim($excepcion['mensaje'] ?? '') !== '';

                if (!$tieneHora && !$tieneMensaje) {
                    $validator->errors()->add("excepciones_por_fecha.$index.hora", 'Indica una hora o un mensaje para la fecha.');
                }
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
            'configuracion_por_mes.*.mes.distinct' => __('No puede haber dos configuraciones personalizadas para el mismo mes'),
            'configuracion_por_mes.*.hora.date_format' => __('La hora personalizada debe tener formato hh:mm'),
            'horarios_por_dia.*.date_format' => __('El horario por dia debe tener formato hh:mm'),
            'configuracion_por_mes.*.horarios_por_dia.*.date_format' => __('El horario por dia debe tener formato hh:mm'),
            'excepciones_por_fecha.*.fecha.required' => __('La fecha de la excepcion es obligatoria'),
            'excepciones_por_fecha.*.fecha.date_format' => __('La fecha de la excepcion debe tener formato aaaa-mm-dd'),
            'excepciones_por_fecha.*.fecha.distinct' => __('No puede haber dos excepciones para la misma fecha'),
            'excepciones_por_fecha.*.hora.date_format' => __('La hora de la excepcion debe tener formato hh:mm'),
            'excepciones_por_fecha.*.mensaje.max' => __('El mensaje no puede superar los 255 caracteres'),
        ];
    }
}
