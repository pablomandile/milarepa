<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketRequest extends FormRequest
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
            'asunto' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable','string'],
            'fecha_apertura' => ['required','date'],
            'user_id' => ['required', 'exists:users,id'],
            'responsable_id' => ['nullable'],
            'estadoticket_id' => ['required', 'exists:estados_ticket,id'],
        ];
    }

    public function messages():array {
        return [
            'asunto.required' => __('El asunto no puede quedar vacío'),
        ];
    }
}
