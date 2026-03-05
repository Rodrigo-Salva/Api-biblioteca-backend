<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookRequest extends FormRequest
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
        $book = $this->route('book');
        $bookId = is_object($book) ? $book->id : $book;

        return [
            'title' => 'sometimes|string',
            'isbn' => 'sometimes|string|unique:books,isbn,' . $bookId,
            'year' => 'sometimes|integer',
            'author_id' => 'sometimes|exists:authors,id',
            'category_id' => 'sometimes|exists:categories,id',
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
