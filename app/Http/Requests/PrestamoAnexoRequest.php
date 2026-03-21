<?php

namespace App\Http\Requests;

use App\Models\Entidad;
use App\Models\InventarioEntidadLibro;
use Illuminate\Foundation\Http\FormRequest;

class PrestamoAnexoRequest extends FormRequest
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
            'prestadora_id' => ['required', 'integer', 'exists:entidades,id', 'different:receptora_id'],
            'receptora_id' => ['required', 'integer', 'exists:entidades,id'],
            'libro_id' => ['required', 'integer', 'exists:libros,id'],
            'cantidad' => [
                'required',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $libroId = (int) $this->input('libro_id');

                    if ($libroId <= 0) {
                        return;
                    }

                    $entidadPrincipalId = Entidad::query()->where('entidad_principal', true)->value('id');
                    $inventario = InventarioEntidadLibro::query()
                        ->where('entidad_id', $entidadPrincipalId)
                        ->where('libro_id', $libroId)
                        ->first();
                    $cantidadSolicitada = (int) $value;
                    $cantidadDisponible = (int) ($inventario?->cantidad ?? 0);

                    if ($cantidadSolicitada > $cantidadDisponible) {
                        $fail('La cantidad no puede exceder el inventario disponible del libro (' . $cantidadDisponible . ').');
                    }
                },
            ],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'La fecha es obligatoria.',
            'prestadora_id.required' => 'Debe seleccionar una entidad prestadora.',
            'receptora_id.required' => 'Debe seleccionar una entidad receptora.',
            'prestadora_id.different' => 'La entidad prestadora y receptora deben ser distintas.',
            'libro_id.required' => 'Debe seleccionar un libro.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.min' => 'La cantidad debe ser mayor o igual a 1.',
        ];
    }
}
