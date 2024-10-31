<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'authors' => 'required|array',
            'authors.*' => 'string|max:255',
            'isbn' => $this->isMethod('PUT') ? 'sometimes|string|max:13' : 'required|string|max:13',
            'copies_count' => 'required|integer|min:1',
            'available_copies' => 'required|integer|min:0|lte:copies_count',
            'parts_count' => 'required|integer|min:1',
            'publisher' => 'required|string|max:255',
            'edition_number' => 'required|integer|min:1',
            'dewey_category' => 'required|string|max:255',
            'dewey_subcategory' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'comments' => 'nullable|string',
            'central_number' => 'required|string|max:255|unique:books,central_number,' . $this->book?->id,
            'local_number' => 'required|string|max:255|unique:books,local_number,' . $this->book?->id,
            'publication_date' => 'required|date',
            'acquisition_date' => 'required|date',
            'publication_year' => 'required|integer|min:1000|max:' . (date('Y') + 1),
        ];

        return $rules;
    }
} 