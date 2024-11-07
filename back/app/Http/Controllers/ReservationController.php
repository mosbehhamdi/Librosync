<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Book;
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

    private function hasActiveReservation(Book $book): bool
    {
        return $book->reservations()
            ->where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'ready'])
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
        $reservations = auth()->user()->reservations()
            ->with(['book'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($reservations);
    }

  /*  public function getReservationByBookId(Book $book): JsonResponse
    {
        try {
            $reservation = $book->reservations()
                ->where('user_id', auth()->id())
                ->first();

            if (!$reservation) {
                return response()->json(['message' => 'No reservation found for this book'], 404);
            }

            return response()->json($reservation);
        } catch (\Exception $e) {
            \Log::error('Error fetching reservation by book ID:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error fetching reservation'], 500);
        }
    } */
}
