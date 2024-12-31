<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketRequest extends FormRequest
{
    public function prepareForValidation()
    {
        // Si no viene estadoticket_id en el request, o viene nulo, forzamos un valor
        if (!$this->has('estadoticket_id') || is_null($this->input('estadoticket_id'))) {
            $this->merge([
                'estadoticket_id' => 1, // ID del estado que quieras por defecto (Pendiente, por ejemplo)
            ]);
        }
    }
    
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
            'asunto' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable','string'],
            'fecha_apertura' => ['required','date'],
            'user_id' => ['required', 'exists:users,id'],
            'responsable_id' => ['nullable'],
            'estadoticket_id' => ['nullable', 'exists:estados_ticket,id'],
        ];
    }

    public function messages():array {
        return [
            'asunto.required' => __('El asunto no puede quedar vacío'),
        ];
    }
}
