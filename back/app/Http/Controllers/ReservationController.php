<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
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
} 