<?php

namespace App\Http\Requests;

use App\Models\Entidad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventarioOracionRequest extends FormRequest
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
        $inventario = $this->route('inventario_oracion');
        $entidadPrincipalId = Entidad::query()->where('entidad_principal', true)->value('id');

        return [
            'oracion_id' => [
                'required',
                'integer',
                'exists:oraciones,id',
                Rule::unique('inventario_entidad_oracion', 'oracion_id')
                    ->where(fn ($query) => $query->where('entidad_id', $entidadPrincipalId ?: 0))
                    ->ignore($inventario?->id),
            ],
            'cantidad' => ['required', 'integer', 'min:0'],
        ];
    }
}
