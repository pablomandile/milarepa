<?php

namespace App\Http\Requests;

use App\Models\Entidad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventarioLibroRequest extends FormRequest
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
        $inventario = $this->route('inventario_libro');
        $entidadPrincipalId = Entidad::query()->where('entidad_principal', true)->value('id');

        return [
            'libro_id' => [
                'required',
                'integer',
                'exists:libros,id',
                Rule::unique('inventario_entidad_libro', 'libro_id')
                    ->where(fn ($query) => $query->where('entidad_id', $entidadPrincipalId ?: 0))
                    ->ignore($inventario?->id),
            ],
            'cantidad' => ['required', 'integer', 'min:0'],
        ];
    }
}
