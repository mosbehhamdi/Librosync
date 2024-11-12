<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Determine if the user is an admin and set up the initial query accordingly
        $query = auth()->user()->isAdmin()
            ? Reservation::query()->with(['user', 'book'])
            : auth()->user()->reservations()->with(['book', 'user']);

        try {
            // Apply search filter
            if ($search = $request->input('search')) {
                $query->whereHas('book', function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhereJsonContains('authors', $search)
                      ->orWhere('publisher', 'like', "%{$search}%");
                });
            }

            // Apply book filter
            if ($bookId = $request->input('book_id')) {
                $query->where('book_id', $bookId);
            }

            // Apply user filter
            if ($userId = $request->input('user_id')) {
                $query->where('user_id', $userId);
            }

            // Apply status filter
            if ($status = $request->input('status')) {
                $query->where('status', $status);
            }

            $reservations = $query->orderByRaw("COALESCE(updated_at, created_at) DESC")
            ->paginate($request->input('per_page', 15));


            // Return paginated data as JSON
            return response()->json([
                'data' => $reservations->items(),
                'current_page' => $reservations->currentPage(),
                'last_page' => $reservations->lastPage(),
                'total' => $reservations->total(),
                'per_page' => $reservations->perPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching reservations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancel(Reservation $reservation): JsonResponse
    {
        // If the user is an admin, skip the user_id check
        if (!auth()->user()->isAdmin() && $reservation->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reservation->status = 'cancelled';
        $reservation->save();

        if (auth()->user()->isAdmin()) {
            // Increment available copies
            $reservation->book->increment('available_copies');

            // Update queue positions
            $this->updateQueuePositions($reservation->book_id);
            return response()->json(['message' => 'Reservation cancelled successfully', 'reservation' => $reservation]);
        }

        return response()->json(['message' => 'Reservation cancelled successfully']);
    }




    private function updateQueuePositions($bookId)
    {
        $reservations = Reservation::where('book_id', $bookId)
            ->where('status', 'pending')
            ->orderBy('queue_position')
            ->get();
        foreach ($reservations as $index => $reservation) {
            $reservation->queue_position = $index + 1;
            $reservation->save();
        }
    }


    private function hasActiveReservation(Book $book): bool
    {
        return $book->reservations()
            ->where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'ready','accepted'])
            ->exists();
    }

    public function store(Book $book): JsonResponse
    {
        try {
            if ($this->hasActiveReservation($book)) {
                return response()->json([
                    'message' => 'You already have an active reservation for this book'
                ], 422);
            }

            // Create reservation
            $reservation = $book->reservations()->create([
                'user_id' => auth()->id(),
                'status' => $book->available_copies > 0 ? 'ready' : 'pending',
                'queue_position' => $book->activeReservations()->count() + 1
            ]);

            return response()->json([
                'message' => $book->available_copies > 0
                    ? 'Book reserved successfully! Please pick it up within 48 hours.'
                    : 'Added to waitlist. We\'ll notify you when the book is available.',
                'reservation' => $reservation
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Reservation creation failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to create reservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function handleExpiredReservations()
    {
        try {
            $expiredReservations = Reservation::where('status', 'ready')
                ->where('expires_at', '<', now())
                ->get();

            foreach ($expiredReservations as $reservation) {
                $reservation->status = 'cancelled';
                $reservation->save();

                // Update queue positions and promote next in line
                $this->promoteNextInQueue($reservation->book_id);
            }

            return response()->json(['message' => 'Expired reservations processed']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to process expired reservations'], 500);
        }
    }

    private function promoteNextInQueue(int $bookId)
    {
        $nextInQueue = Reservation::where('book_id', $bookId)
            ->where('status', 'pending')
            ->orderBy('queue_position')
            ->first();

        if ($nextInQueue) {
            $nextInQueue->status = 'ready';
            $nextInQueue->expires_at = now()->addHours(48);
            $nextInQueue->save();

            // Send notification to user
            $this->sendNotification($nextInQueue->user, 'Your reservation is ready for pickup.');
        }
    }

    private function sendNotification($user, $message)
    {
        // Implement notification logic here
    }

    public function joinWaitlist(Book $book): JsonResponse
    {
        try {
            // Add user to the waitlist
            $reservation = $book->reservations()->create([
                'user_id' => auth()->id(),
                'book_id' => $book->id,
                'status' => 'pending',
                'queue_position' => $book->activeReservations()->count() + 1
            ]);

            return response()->json([
                'message' => 'Added to waitlist. We\'ll notify you when the book is available.',
                'reservation' => $reservation
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Failed to join waitlist:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to join waitlist',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getQueuePosition(Book $book): JsonResponse
    {
        try {
            $reservation = $book->reservations()
                ->where('user_id', auth()->id())
                ->where('status', 'pending')
                ->first();

            if (!$reservation) {
                return response()->json([
                    'queue_position' => null,
                    'message' => 'No pending reservation found for this book'
                ]);
            }

            return response()->json([
                'queue_position' => $reservation->queue_position
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get queue position:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to get queue position',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function history(): JsonResponse
    {
        if (auth()->user()->isAdmin()) {
            $reservations = Reservation::with(['user', 'book'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $reservations = auth()->user()->reservations()
                ->with(['book'])
                ->orderBy('created_at', 'desc')
                ->get();
        }
        return response()->json($reservations);
    }

    public function reservationStatistics(): JsonResponse
    {
        $totalReservations = Reservation::count();
        $activeReservations = Reservation::where('status', 'active')->count();
        $deliveredReservations = Reservation::where('status', 'delivered')->count();
        $cancelledReservations = Reservation::where('status', 'cancelled')->count();

        $statistics = [
            'total' => $totalReservations,
            'active' => $activeReservations,
            'delivered' => $deliveredReservations,
            'cancelled' => $cancelledReservations,
        ];

        return response()->json($statistics);
    }

    public function accept(Reservation $reservation): JsonResponse
    {
        // Ensure the user is an admin
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Use a transaction to ensure atomicity
        $updatedReservation = DB::transaction(function () use ($reservation) {
            // Update available copies if immediate reservation
            if ($reservation->book->available_copies > 0) {
                $reservation->book->decrement('available_copies');
            }

            // Update the reservation status to 'accepted'
            $reservation->status = 'accepted';
            $reservation->save();

            // Check for the next pending reservation
            $nextReservation = $reservation->book->reservations()
                ->where('status', 'pending')
                ->orderBy('queue_position')
                ->first();

            if ($nextReservation) {
                $nextReservation->status = 'ready';
                $nextReservation->save();
            }

            return $reservation; // Return the updated reservation
        });

        return response()->json(['message' => 'Reservation accepted successfully', 'reservation' => $updatedReservation]);
    }

    public function deliver(Reservation $reservation): JsonResponse
    {
        // Ensure the user is an admin
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Update the reservation status to 'delivered'
        $reservation->status = 'delivered';
        $reservation->save();

        return response()->json(['message' => 'Reservation marked as delivered successfully', 'reservation' => $reservation]);
    }
}
