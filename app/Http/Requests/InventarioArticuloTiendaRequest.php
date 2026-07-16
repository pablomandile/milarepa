<?php

namespace App\Http\Requests;

use App\Models\Entidad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventarioArticuloTiendaRequest extends FormRequest
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
        $inventario = $this->route('inventario_articulo_tienda');
        $entidadPrincipalId = Entidad::query()->where('entidad_principal', true)->value('id');

        return [
            'articulo_tienda_id' => [
                'required',
                'integer',
                'exists:articulos_tienda,id',
                Rule::unique('inventario_entidad_articulo_tienda', 'articulo_tienda_id')
                    ->where(fn ($query) => $query->where('entidad_id', $entidadPrincipalId ?: 0))
                    ->ignore($inventario?->id),
            ],
            'cantidad' => ['required', 'integer', 'min:0'],
        ];
    }
}
