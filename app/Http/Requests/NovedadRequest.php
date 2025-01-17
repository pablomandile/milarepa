<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NovedadRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['string', 'max:255'],
            'fecha' => ['required', 'date']      
        ];
    }

    public function messages():array {
        return [
            'nombre.required' => __('El nombre no puede quedar vacío'),
            'fecha.required' => __('La fecha no puede quedar vacía')
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('fecha')) {
            $this->merge([
                'fecha' => \Carbon\Carbon::parse($this->fecha)->format('Y-m-d'),
            ]);
        }
    }
}
