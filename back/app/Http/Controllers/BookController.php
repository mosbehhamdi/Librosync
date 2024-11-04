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
try {
$books = Book::with('reservations')
->when($request->search, function($query, $search) {
$query->search($search);
})
->orderBy('title')
->paginate($request->per_page ?? 10);

$books->getCollection()->transform(function ($book) {
$book->user_reservation = $book->reservations->first();
$book->waiting_time = $book->getWaitingTimeAttribute();
unset($book->reservations);
return $book;
});

// Debug log
\Log::info('Query results:', [
'count' => $books->count(),
'total' => $books->total()
]);

return response()->json($books);
} catch (\Exception $e) {
\Log::error('Book search error:', [
'message' => $e->getMessage(),
'trace' => $e->getTraceAsString()
]);
return response()->json([
'message' => 'Error fetching books',
'error' => $e->getMessage()
], 500);
}
}

public function adminIndex(Request $request): JsonResponse
{
try {
$query = Book::with(['reservations']);
// Handle search
if ($request->has('search') && !empty($request->search)) {
$searchTerm = $request->search;
$query->where(function($q) use ($searchTerm) {
$q->where('title', 'like', "%{$searchTerm}%")
->orWhereJsonContains('authors', $searchTerm)
->orWhere('publisher', 'like', "%{$searchTerm}%");
});
}
// Handle category filter - exact match for dewey_category
if ($request->has('category') && !empty($request->category)) {
$query->where('dewey_category', substr($request->category, 0, 3));
}
\Log::info('Query debug:', [
'SQL' => $query->toSql(),
'Bindings' => $query->getBindings(),
'Category' => $request->category
]);
$books = $query->orderBy('created_at', 'desc')
->paginate($request->per_page ?? 10);
return response()->json([
'data' => $books->items(),
'current_page' => $books->currentPage(),
'last_page' => $books->lastPage(),
'total' => $books->total()
]);
} catch (\Exception $e) {
\Log::error('Error in adminIndex:', [
'message' => $e->getMessage(),
'trace' => $e->getTraceAsString()
]);
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
$book->user_reservation = $book->reservations->first();
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
}
