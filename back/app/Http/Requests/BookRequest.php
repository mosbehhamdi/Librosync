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
            'title.required' => 'book.validation.title.required',
            'title.max' => 'book.validation.title.max',
            'authors.required' => 'book.validation.authors.required',
            'authors.*.string' => 'book.validation.authors.string',
            'copies_count.required' => 'book.validation.copies.required',
            'copies_count.integer' => 'book.validation.copies.integer',
            'copies_count.min' => 'book.validation.copies.min',
            'available_copies.required' => 'book.validation.available.required',
            'available_copies.integer' => 'book.validation.available.integer',
            'available_copies.max' => 'book.validation.copies.max',
            'parts_count.required' => 'book.validation.parts.required',
            'parts_count.integer' => 'book.validation.parts.integer',
            'edition_number.required' => 'book.validation.edition.required',
            'edition_number.integer' => 'book.validation.edition.integer',
            'dewey_category.required' => 'book.validation.dewey.required',
            'dewey_subcategory.max' => 'book.validation.dewey.max',
            'publisher.max' => 'book.validation.publisher.max',
            'central_number.max' => 'book.validation.numbers.max',
            'central_number.unique' => 'book.validation.numbers.uniqueCentral',
            'local_number.max' => 'book.validation.numbers.max',
            'local_number.unique' => 'book.validation.numbers.uniqueLocal',
            'isbn.required' => 'book.validation.isbn.required',
            'isbn.regex' => 'book.validation.isbn.format',
            'publication_year.required' => 'book.validation.publication.required',
            'publication_year.integer' => 'book.validation.publication.integer',
            'publication_year.min' => 'book.validation.publication.min',
            'publication_year.max' => 'book.validation.publication.future'
        ];
    }
}
