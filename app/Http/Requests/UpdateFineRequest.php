<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'amount' => 'sometimes|required|numeric|min:0.01|max:9999.99',
            'days_overdue' => 'sometimes|required|integer|min:1',
            'status' => 'sometimes|required|in:pendiente,pagada',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.numeric' => 'El monto debe ser numérico.',
            'amount.min' => 'El monto mínimo es 0.01.',
            'amount.max' => 'El monto máximo es 9999.99.',
            'days_overdue.integer' => 'Los días de retraso deben ser un número entero.',
            'days_overdue.min' => 'Los días de retraso mínimos son 1.',
            'status.in' => 'El estado debe ser: pendiente o pagada.',
        ];
    }
}