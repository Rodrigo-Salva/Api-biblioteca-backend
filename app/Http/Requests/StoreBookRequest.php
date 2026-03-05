<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
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
            'title' => 'required|string',
            'isbn' => 'required|string|unique:books',
            'year' => 'required|integer',
            'author_id' => 'required|exists:authors,id',
            'category_id' => 'required|exists:categories,id',
            'cover_image' => 'nullable|image|max:2048',
            'digital_file' => 'nullable|mimes:pdf,epub|max:10240',
            'is_digital' => 'nullable|boolean',
            'synopsis' => 'nullable|string',
            'pages' => 'nullable|integer',
            'publisher' => 'nullable|string',
            'stock' => 'nullable|integer|min:0',
        ];
    }
}
