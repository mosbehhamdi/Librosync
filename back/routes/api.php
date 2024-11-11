<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;


require 'auth.php';


Route::middleware('auth:api')->group(function () {
    // Books
    Route::get('/books', [BookController::class, 'index']);

    Route::get('/books/category/{category}', [BookController::class, 'getByCategory']);
    Route::get('/books/{book}', [BookController::class, 'refreshBook']);
    Route::post('/books/{book}/reserve', [ReservationController::class, 'store']);

    // User reservations
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::get('/reservations/expired', [ReservationController::class, 'handleExpiredReservations']);
    Route::get('/reservations/history', [ReservationController::class, 'history']);
    Route::get('/books/{book}/queue-position', [ReservationController::class, 'getQueuePosition']);
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);

    //admin reservations
    Route::post('/admin/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);
    Route::get('/admin/reservations/statistics', [ReservationController::class, 'reservationStatistics']);
    Route::post('/admin/reservations/{reservation}/accept', [ReservationController::class, 'accept']);
    Route::post('/admin/reservations/{reservation}/deliver', [ReservationController::class, 'deliver']);
    Route::middleware('auth:api')->group(function () {
        Route::post('/books/{book}/waitlist', [ReservationController::class, 'joinWaitlist']);
    });
});

// Admin routes
Route::middleware(['auth:api', 'admin'])->group(function () {
    // Dashboard
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);

    // Users management
    Route::get('/admin/users', [AdminController::class, 'users']);
    Route::delete('/admin/users/{user}', [AdminController::class, 'deleteUser']);

    // Books management
    Route::post('/admin/books', [BookController::class, 'store']);
    Route::get('/admin/books/{book}', [BookController::class, 'show']);
    Route::put('/admin/books/{book}', [BookController::class, 'update']);
    Route::delete('/admin/books/{book}', [BookController::class, 'destroy']);
    Route::put('/admin/books/{book}/copies', [BookController::class, 'updateCopies']);


});
