<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fecha' => ['nullable', 'date'],
            'cliente_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'entidad_id' => ['required', 'integer', 'exists:entidades,id'],
            'metodo_pago_id' => ['required', 'integer', 'exists:metodos_pago,id'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'comprobante' => ['nullable', 'image', 'max:4096'],
            'comprobante_id' => ['nullable', 'integer', 'exists:imagenes,id'],
            'idempotency_key' => ['nullable', 'uuid'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.tipo' => ['required', Rule::in(['libro', 'oracion', 'arte', 'otro', 'articulo_tienda', 'inscripcion_actividad', 'inscripcion_clase'])],
            'items.*.producto_id' => ['nullable', 'integer'],
            'items.*.cantidad' => ['nullable', 'integer', 'min:1'],
            // Payload de inscripción (para líneas inscripcion_clase / inscripcion_actividad).
            // La validación fina la hace el servicio de inscripción correspondiente.
            'items.*.inscripcion' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'entidad_id.required' => 'Elegí la entidad (caja) de la venta.',
            'metodo_pago_id.required' => 'Elegí el medio de pago.',
            'items.required' => 'Agregá al menos un ítem al carrito.',
            'items.min' => 'Agregá al menos un ítem al carrito.',
        ];
    }
}
