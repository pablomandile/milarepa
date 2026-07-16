<?php

namespace App\Http\Requests;

use App\Models\Entidad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventarioOtroRequest extends FormRequest
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
        $inventario = $this->route('inventario_otro');
        $entidadPrincipalId = Entidad::query()->where('entidad_principal', true)->value('id');

        return [
            'otro_id' => [
                'required',
                'integer',
                'exists:otros,id',
                Rule::unique('inventario_entidad_otro', 'otro_id')
                    ->where(fn ($query) => $query->where('entidad_id', $entidadPrincipalId ?: 0))
                    ->ignore($inventario?->id),
            ],
            'cantidad' => ['required', 'integer', 'min:0'],
        ];
    }
}
