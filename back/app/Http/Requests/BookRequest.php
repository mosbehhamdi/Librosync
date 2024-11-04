<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        \Log::info('Validating book request', $this->all());

        $bookId = $this->route('book');

        return [
            'title' => 'required|string|max:255',
            'authors' => 'required|array|min:1',
            'authors.*' => 'string|max:255',
            'copies_count' => 'required|integer|min:1',
            'available_copies' => 'required|integer|min:0|max:' . $this->input('copies_count', 1),
            'parts_count' => 'required|integer|min:1',
            'publisher' => 'nullable|string|max:255',
            'edition_number' => 'required|integer|min:1',
            'dewey_category' => 'required|string|max:255',
            'dewey_subcategory' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'comments' => 'nullable|string',
            'central_number' => 'nullable|string|max:255|unique:books,central_number,' . $bookId,
            'local_number' => 'nullable|string|max:255|unique:books,local_number,' . $bookId,
            'isbn' => 'required|string|regex:/^[0-9]{10,13}$/',
            'publication_date' => 'nullable|date',
            'acquisition_date' => 'nullable|date',
            'publication_year' => 'required|integer|min:1000|max:' . date('Y')
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'The title field is required.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'authors.required' => 'At least one author is required.',
            'authors.*.string' => 'Each author must be a valid string.',
            'copies_count.required' => 'The number of copies is required.',
            'copies_count.integer' => 'The number of copies must be an integer.',
            'available_copies.required' => 'The available copies field is required.',
            'available_copies.integer' => 'Available copies must be an integer.',
            'available_copies.max' => 'Available copies cannot exceed total copies.',
            'parts_count.required' => 'The number of parts is required.',
            'parts_count.integer' => 'The number of parts must be an integer.',
            'edition_number.required' => 'The edition number is required.',
            'edition_number.integer' => 'The edition number must be an integer.',
            'dewey_category.required' => 'The Dewey category is required.',
            'dewey_subcategory.max' => 'The Dewey subcategory may not be greater than 255 characters.',
            'publisher.max' => 'The publisher may not be greater than 255 characters.',
            'central_number.max' => 'The central number may not be greater than 255 characters.',
            'local_number.max' => 'The local number may not be greater than 255 characters.',
            'isbn.required' => 'The ISBN is required.',
            'isbn.regex' => 'The ISBN must be a 10 or 13-digit number.',
            'publication_year.required' => 'The publication year is required.',
            'publication_year.integer' => 'The publication year must be an integer.',
            'publication_year.min' => 'The publication year must be at least 1000.',
            'publication_year.max' => 'The publication year cannot be in the future.',
        ];
    }
}
