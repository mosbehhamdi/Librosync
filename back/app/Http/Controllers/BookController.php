<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\BookRequest;

class BookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Book::query();

        // Search by title or author
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereJsonContains('authors', $search);
            });
        }

        // Filter by Dewey category
        if ($request->has('category') && !empty($request->category)) {
            $query->where('dewey_category', $request->category);
        }

        $books = $query->orderBy('title')->paginate(10);
        
        // Debug information
        \Log::info('Books query result:', [
            'total' => $books->total(),
            'current_page' => $books->currentPage(),
            'per_page' => $books->perPage(),
            'filters' => $request->all()
        ]);

        return response()->json($books);
    }

    public function store(BookRequest $request): JsonResponse
    {
        $book = Book::create($request->validated());
        return response()->json($book, 201);
    }

    public function show(Book $book): JsonResponse
    {
        return response()->json($book);
    }

    public function update(BookRequest $request, Book $book): JsonResponse
    {
        $book->update($request->validated());
        return response()->json($book);
    }

    public function destroy(Book $book): JsonResponse
    {
        $book->delete();
        return response()->json(['message' => 'Book deleted successfully']);
    }

    public function updateCopies(Request $request, Book $book): JsonResponse
    {
        $request->validate([
            'copies_count' => 'required|integer|min:1',
            'available_copies' => 'required|integer|min:0|lte:copies_count'
        ]);

        $book->update($request->only(['copies_count', 'available_copies']));
        return response()->json($book);
    }

    public function search(Request $request): JsonResponse
    {
        $query = Book::query();

        if ($request->has('title')) {
            $query->where('title', 'like', "%{$request->title}%");
        }

        if ($request->has('dewey_category')) {
            $query->where('dewey_category', $request->dewey_category);
        }

        if ($request->has('author')) {
            $query->whereJsonContains('authors', $request->author);
        }

        if ($request->has('query')) {
            $search = $request->query;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereJsonContains('authors', $search)
                  ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        return response()->json(['data' => $query->paginate(10)]);
    }
} 