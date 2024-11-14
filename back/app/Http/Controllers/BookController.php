<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Http\Requests\BookRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Book::with('reservations');

        try {
            // Apply search filter
            if ($search = $request->input('search')) {
                $query->where('title', 'like', "%{$search}%")
                      ->orWhereJsonContains('authors', $search)
                      ->orWhere('publisher', 'like', "%{$search}%");
            }

            // Apply category filter
            if ($category = $request->input('category')) {
                $query->where('dewey_category', substr($category, 0, 3));
            }
            // Determine sorting based on user role
            $orderBy = auth()->user()->isAdmin() ? 'created_at' : 'title';
            $books = $query->orderBy($orderBy, 'desc')
                           ->paginate($request->input('per_page', 10));

            // Additional transformation for non-admin users
            if (!auth()->user()->isAdmin()) {
                $books->getCollection()->transform(function ($book) {
                    $book->user_reservation = $book->reservations
                        ->where('user_id', auth()->id())
                        ->whereIn('status', ['pending', 'ready', 'accepted', 'delivered'])
                        ->first();
                    $book->waiting_time = $book->getWaitingTimeAttribute();
                    unset($book->reservations);
                    return $book;
                });
            }

            return response()->json([
                'data' => $books->items(),
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'total' => $books->total()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching books',
                'error' => $e->getMessage()
            ], 500);
        }
    }



public function store(BookRequest $request)
{
    try {
        $book = Book::create($request->validated());
        return response()->json($book, 201);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function update(BookRequest $request, $id)
{
    $book = Book::findOrFail($id);
    try {
        $book->update($request->validated());
        return response()->json($book);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function show(Book $book): JsonResponse
{
$book->load(['reservations.user']);
return response()->json($book);
}


public function destroy(Book $book): JsonResponse
{
$book->delete();
return response()->json(null, 204);
}

public function updateCopies(Request $request, Book $book): JsonResponse
{
$validated = $request->validate([
'copies_count' => 'required|integer|min:0',
'available_copies' => 'required|integer|min:0'
]);

$book->update($validated);
return response()->json($book);
}

public function getByCategory(string $category): JsonResponse
{
try {
$books = Book::where('dewey_category', $category)
->with('reservations')
->paginate(10);

$books->getCollection()->transform(function ($book) {
$book->user_reservation = $book->reservations
    ->where('user_id', auth()->id())
    ->first();
$book->waiting_time = $book->getWaitingTimeAttribute();
unset($book->reservations);
return $book;
});

return response()->json([
'data' => $books->items(),
'meta' => [
'current_page' => $books->currentPage(),
'last_page' => $books->lastPage(),
'total' => $books->total()
],
'message' => $books->isEmpty() ? 'No books found in this category' : null
]);
} catch (\Exception $e) {
return response()->json([
'message' => 'Error fetching books by category',
'error' => $e->getMessage()
], 500);
}
}

public function refreshBook(Book $book): JsonResponse
{
    try {
        $book->load('reservations');
        $book->user_reservation = $book->reservations
            ->where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'ready','accepted'])
            ->first();
        $book->waiting_time = $book->getWaitingTimeAttribute();
        unset($book->reservations);

        return response()->json($book);
    } catch (\Exception $e) {
        \Log::error('Error refreshing book:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'message' => 'Error fetching book',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
