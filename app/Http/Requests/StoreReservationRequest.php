<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'book_id' => 'required|integer|exists:books,id',
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => 'El ID del libro es obligatorio.',
            'book_id.integer' => 'El ID del libro debe ser un número entero.',
            'book_id.exists' => 'El libro seleccionado no existe.',
        ];
    }
}