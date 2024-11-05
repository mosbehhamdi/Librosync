<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminReservationController extends Controller
{
    public function index(): JsonResponse
    {
        $reservations = Reservation::with(['user', 'book'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($reservations);
    }

    public function cancel(Reservation $reservation): JsonResponse
    {
        $reservation->status = 'cancelled';
        $reservation->save();

        // Increment available copies
        $reservation->book->increment('available_copies');

        // Update queue positions
        $this->updateQueuePositions($reservation->book_id);

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

    public function history(): JsonResponse
    {
        $reservations = Reservation::with(['user', 'book'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($reservations);
    }

    public function accept(Reservation $reservation): JsonResponse
    {
        // Ensure the user is an admin
        if (!auth()->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

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

        return response()->json(['message' => 'Reservation accepted successfully']);
    }

    public function deliver(Reservation $reservation): JsonResponse
    {
        // Ensure the user is an admin
        if (!auth()->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Update the reservation status to 'delivered'
        $reservation->status = 'delivered';
        $reservation->save();

        return response()->json(['message' => 'Reservation marked as delivered successfully']);
    }
}
