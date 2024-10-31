<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReservationController extends Controller
{
    public function index(): JsonResponse
    {
        $reservations = auth()->user()->reservations()
            ->with(['book', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($reservations);
    }

    public function cancel(Reservation $reservation): JsonResponse
    {
        if ($reservation->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reservation->status = 'cancelled';
        $reservation->save();
        
        return response()->json(['message' => 'Reservation cancelled successfully']);
    }

    public function store(Book $book): JsonResponse
    {
        try {
            // Check for existing reservation
            $existingReservation = $book->reservations()
                ->where('user_id', auth()->id())
                ->whereIn('status', ['pending', 'ready'])
                ->first();

            if ($existingReservation) {
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

            // Update available copies if immediate reservation
            if ($book->available_copies > 0) {
                $book->decrement('available_copies');
            }

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
            // TODO: Implement notification system
        }
    }
} 