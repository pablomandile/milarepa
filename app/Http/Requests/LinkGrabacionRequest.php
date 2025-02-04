<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkGrabacionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules(): array
    {
        return [
            'nombre' => ['string', 'max:50'],
            'link' => ['required', 'string', 'max:255']
        ];
    }

    public function messages(): array
    {
        return [
            'link.required' => __('El link no puede quedar vacío'),
        ];
    }
}
