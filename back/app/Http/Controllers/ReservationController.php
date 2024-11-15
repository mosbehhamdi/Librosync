<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Events\ReservationDueDateSet;
use App\Notifications\ReservationAcceptedNotification;
use App\Notifications\ReservationReadyNotification;

class ReservationController extends Controller
{
    private const MAX_Delivered_BOOKS = 3;

    public function index(Request $request): JsonResponse
    {
        \Log::info('Reservation index called with params:', $request->all());

        $query = auth()->user()->isAdmin()
            ? Reservation::query()->with(['user', 'book'])
            : auth()->user()->reservations()->with(['book', 'user']);

        try {
            // Apply search filter
            if ($search = $request->input('search')) {
                $query->where(function($q) use ($search) {
                    $q->whereHas('book', function($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%")
                          ->orWhereJsonContains('authors', $search);
                    })
                    ->orWhereHas('user', function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    });
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
                if (is_array($status)) {
                    $query->whereIn('status', $status);
                } else {
                    $query->where('status', $status);
                }
            }

            // Apply type filter (active/past)
            if ($type = $request->input('type')) {
                if ($type === 'active') {
                    $query->whereIn('status', Reservation::ACTIVE_STATUSES);
                } elseif ($type === 'past') {
                    $query->whereIn('status', Reservation::PAST_STATUSES);
                }
            }

            $reservations = $query
                ->orderBy('updated_at', 'desc')
                ->paginate($request->input('per_page', 15));

            return response()->json([
                'data' => $reservations->items(),
                'current_page' => $reservations->currentPage(),
                'last_page' => $reservations->lastPage(),
                'total' => $reservations->total(),
                'per_page' => $reservations->perPage(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in reservation index:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error fetching reservations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancel(Reservation $reservation): JsonResponse
    {
        if (!auth()->user()->isAdmin() && $reservation->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::transaction(function () use ($reservation) {
            $oldStatus = $reservation->status;
            $reservation->status = 'cancelled';
            $reservation->save();

            $this->handleQueuePositionUpdate($reservation, 'cancel');

            if ($oldStatus === 'delivered') {
                $reservation->book->increment('available_copies');
            }
        });

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
            ->whereIn('status', [
                Reservation::STATUS_PENDING,
                Reservation::STATUS_READY,
                Reservation::STATUS_ACCEPTED,
                Reservation::STATUS_DELIVERED
            ])
            ->exists();
    }

    public function store(Book $book): JsonResponse
    {
        try {
            return DB::transaction(function () use ($book) {
                if ($this->hasOverdueBooks()) {
                    return response()->json([
                        'message' => 'You have overdue books. Please return them before making new reservations.'
                    ], 422);
                }

                if ($this->countCurrentlyDelivered() >= self::MAX_Delivered_BOOKS) {
                    return response()->json([
                        'message' => 'You can have maximum ' . self::MAX_Delivered_BOOKS . ' books at a time. Please return some books first.'
                    ], 422);
                }

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

                // If status is ready, set expiration
                if ($reservation->status === 'ready') {
                    $reservation->expires_at = now()->addHours(48);
                    $reservation->save();
                }

                return response()->json([
                    'message' => $book->available_copies > 0
                        ? 'Book reserved successfully! Please pick it up within 48 hours.'
                        : 'Added to waitlist. We\'ll notify you when the book is available.',
                    'reservation' => $reservation
                ], 201);
            });
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

            // Notify user with the new notification class
            $nextInQueue->user->notify(new ReservationReadyNotification($nextInQueue));
        }
    }

    private function sendNotification($user, $message)
    {
        // Implement notification logic here
    }

    public function joinWaitlist(Book $book): JsonResponse
    {
        try {
            return DB::transaction(function () use ($book) {
                if ($this->hasOverdueBooks()) {
                    return response()->json([
                        'message' => 'You have overdue books. Please return them before making new reservations.'
                    ], 422);
                }

                if ($this->countCurrentlyDelivered() >= self::MAX_Delivered_BOOKS) {
                    return response()->json([
                        'message' => 'You can have maximum ' . self::MAX_Delivered_BOOKS . ' books at a time. Please return some books first.'
                    ], 422);
                }

                if ($this->hasActiveReservation($book)) {
                    return response()->json([
                        'message' => 'You already have an active reservation for this book'
                    ], 422);
                }

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
            });
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
        try {
            $query = auth()->user()->isAdmin()
                ? Reservation::with(['user', 'book'])
                : auth()->user()->reservations()->with(['book']);

            // Don't filter by status for history - we want all statuses
            $reservations = $query
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            $data = $reservations->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'book' => $reservation->book,
                    'user' => $reservation->user,
                    'status' => $reservation->status,
                    'created_at' => $reservation->created_at,
                    'updated_at' => $reservation->updated_at,
                    'expires_at' => $reservation->expires_at,
                    'delivered_at' => $reservation->delivered_at,
                    'returned_at' => $reservation->returned_at,
                    'due_date' => $reservation->due_date,
                    'queue_position' => $reservation->queue_position,
                    // Add status change timestamps
                    'status_history' => [
                        'reserved' => $reservation->created_at,
                        'ready' => $reservation->ready_at,
                        'accepted' => $reservation->accepted_at,
                        'delivered' => $reservation->delivered_at,
                        'returned' => $reservation->returned_at,
                        'cancelled' => $reservation->cancelled_at
                    ]
                ];
            });

            return response()->json([
                'data' => $data,
                'current_page' => $reservations->currentPage(),
                'last_page' => $reservations->lastPage(),
                'total' => $reservations->total(),
                'per_page' => $reservations->perPage(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching reservation history:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error fetching reservation history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getActionType(string $status): string
    {
        $actionTypes = [
            'pending' => 'Reserved and waiting',
            'ready' => 'Ready for pickup',
            'accepted' => 'Accepted by library',
            'delivered' => 'Delivered',
            'returned' => 'Returned to library',
            'cancelled' => 'Cancelled'
        ];
        return $actionTypes[$status] ?? $status;
    }

    public function reservationStatistics(): JsonResponse
    {
        $statistics = [
            'total' => Reservation::count(),
            'active' => Reservation::whereIn('status', Reservation::ACTIVE_STATUSES)->count(),
            'delivered' => Reservation::where('status', Reservation::STATUS_DELIVERED)->count(),
            'returned' => Reservation::where('status', Reservation::STATUS_RETURNED)->count(),
            'cancelled' => Reservation::where('status', Reservation::STATUS_CANCELLED)->count(),
        ];

        return response()->json($statistics);
    }

    public function accept(Reservation $reservation): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($reservation->status !== 'ready') {
            return response()->json(['message' => 'Can only accept ready reservations'], 422);
        }

        return DB::transaction(function () use ($reservation) {
            // Decrement available copies
            if ($reservation->book->available_copies > 0) {
                $reservation->book->decrement('available_copies');
            }

            // Clear queue position and update status
            $this->clearQueuePosition($reservation);
            $reservation->status = 'accepted';
            $reservation->save();

            // Check for next in queue
            $nextReservation = $reservation->book->reservations()
                ->where('status', 'pending')
                ->orderBy('queue_position')
                ->first();

            if ($nextReservation) {
                $nextReservation->status = 'ready';
                $nextReservation->expires_at = now()->addHours(48);
                $nextReservation->save();

                // Notify next user
                $nextReservation->user->notify(new ReservationReadyNotification($nextReservation));
            }

            // Notify current user
            $reservation->user->notify(new ReservationAcceptedNotification($reservation));

            return response()->json([
                'message' => 'Reservation accepted successfully',
                'reservation' => $reservation
            ]);
        });
    }

    public function deliver(Reservation $reservation): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($reservation->status !== 'accepted') {
            return response()->json(['message' => 'Can only deliver accepted reservations'], 422);
        }

        DB::transaction(function () use ($reservation) {
            $reservation->status = 'delivered';
            $reservation->delivered_at = now();
            $reservation->delivered_at = now();
            $reservation->due_date = now()->addDays(14);
            $reservation->save();

            // Ensure available copies is decremented
            if ($reservation->book->available_copies > 0) {
                $reservation->book->decrement('available_copies');
            }

            // Send due date notification
            event(new ReservationDueDateSet($reservation));
        });

        return response()->json(['message' => 'Book delivered to user', 'reservation' => $reservation]);
    }

    public function returnBook(Reservation $reservation): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($reservation->status !== 'delivered') {
            return response()->json(['message' => 'Can only return delivered books'], 422);
        }

        DB::transaction(function () use ($reservation) {
            $reservation->status = 'returned';
            $reservation->returned_at = now();
            $reservation->save();

            // Increment available copies when book is returned
            $reservation->book->increment('available_copies');

            // Update queue positions and promote next in line
            $this->promoteNextInQueue($reservation->book_id);
        });

        return response()->json([
            'message' => 'Book returned successfully',
            'reservation' => $reservation
        ]);
    }

    private function clearQueuePosition(Reservation $reservation)
    {
        if ($reservation->queue_position) {
            $reservation->queue_position = null;
            $reservation->save();
            $this->updateQueuePositions($reservation->book_id);
        }
    }

    private function hasOverdueBooks(): bool
    {
        return auth()->user()->reservations()
            ->where('status', Reservation::STATUS_DELIVERED)
            ->where('due_date', '<', now())
            ->exists();
    }

    private function countCurrentlyDelivered(): int
    {
        // Include both delivered and accepted books in the count
        return auth()->user()->reservations()
            ->whereIn('status', [
                Reservation::STATUS_DELIVERED,
                Reservation::STATUS_ACCEPTED,
                Reservation::STATUS_READY  // Also count books that are ready for pickup
            ])
            ->count();
    }

    public function getUserReservationStats(): JsonResponse
    {
        $user = auth()->user();

        $delivered_count = Reservation::where('user_id', $user->id)
            ->whereIn('status', [
                Reservation::STATUS_DELIVERED,
                Reservation::STATUS_ACCEPTED
            ])
            ->count();

        $has_overdue = Reservation::where('user_id', $user->id)
            ->where('status', Reservation::STATUS_DELIVERED)
            ->where('due_date', '<', now())
            ->exists();

        return response()->json([
            'delivered_count' => $delivered_count,
            'has_overdue' => $has_overdue,
            'max_allowed' => self::MAX_Delivered_BOOKS
        ]);
    }

    private function handleQueuePositionUpdate(Reservation $reservation, string $action)
    {
        DB::transaction(function () use ($reservation, $action) {
            $bookId = $reservation->book_id;

            switch ($action) {
                case 'cancel':
                    if ($reservation->queue_position) {
                        $this->clearQueuePosition($reservation);
                        $this->updateQueuePositions($bookId);
                    }
                    break;

                case 'accept':
                    if ($reservation->queue_position) {
                        $this->clearQueuePosition($reservation);
                        $this->updateQueuePositions($bookId);
                    }
                    break;

                case 'return':
                    $this->promoteNextInQueue($bookId);
                    break;

                case 'expire':
                    if ($reservation->queue_position) {
                        $this->clearQueuePosition($reservation);
                        $this->updateQueuePositions($bookId);
                        $this->promoteNextInQueue($bookId);
                    }
                    break;
            }
        });
    }
}
