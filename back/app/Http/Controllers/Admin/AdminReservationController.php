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
        
        return response()->json(['message' => 'Reservation cancelled successfully']);
    }

    public function markAsReady(Reservation $reservation): JsonResponse
    {
        $reservation->status = 'ready';
        $reservation->save();
        
        return response()->json($reservation);
    }
} 