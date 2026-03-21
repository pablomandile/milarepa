<?php

namespace App\Http\Requests;

use App\Models\InventarioEntidadLibro;
use Illuminate\Foundation\Http\FormRequest;

class VentaLibroRequest extends FormRequest
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
            'entidad_id' => ['required', 'integer', 'exists:entidades,id'],
            'libro_id' => ['required', 'integer', 'exists:libros,id'],
            'precio_unitario' => ['required', 'numeric', 'min:0'],
            'cantidad' => [
                'required',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $entidadId = (int) $this->input('entidad_id');
                    $libroId = (int) $this->input('libro_id');

                    if ($entidadId <= 0 || $libroId <= 0) {
                        return;
                    }

                    $inventario = InventarioEntidadLibro::query()
                        ->where('entidad_id', $entidadId)
                        ->where('libro_id', $libroId)
                        ->first();

                    $cantidadSolicitada = (int) $value;
                    $cantidadDisponible = (int) ($inventario?->cantidad ?? 0);

                    if ($cantidadSolicitada > $cantidadDisponible) {
                        $fail('La cantidad no puede exceder el stock disponible de la entidad (' . $cantidadDisponible . ').');
                    }
                },
            ],
            'montoTotal' => ['required', 'numeric', 'min:0'],
            'modo' => ['required', 'string', 'max:80'],
            'comprobante_id' => ['nullable', 'integer', 'exists:imagenes,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'La fecha es obligatoria.',
            'entidad_id.required' => 'Debe seleccionar una entidad.',
            'libro_id.required' => 'Debe seleccionar un libro.',
            'precio_unitario.required' => 'El precio unitario es obligatorio.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.min' => 'La cantidad debe ser mayor o igual a 1.',
            'montoTotal.required' => 'El monto total es obligatorio.',
            'modo.required' => 'Debe seleccionar un modo de pago.',
        ];
    }
}
