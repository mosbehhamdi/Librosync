<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\Admin\AdminReservationController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/verify-reset-code', [PasswordResetController::class, 'verifyCode']);
Route::post('/reset-password', [PasswordResetController::class, 'reset']);

// Auth routes
Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    // Protected routes
    Route::middleware('auth:api')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
});

// Email verification routes (protected)
Route::middleware('auth:api')->group(function () {
    Route::post('/email/verify', [VerificationController::class, 'verify']);
    Route::post('/email/resend', [VerificationController::class, 'resend']);
});

// Admin routes
Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {
    Route::get('/users', [AdminController::class, 'users']);
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);
    Route::get('/reservations', [AdminReservationController::class, 'index']);
    Route::post('/reservations/{reservation}/cancel', [AdminReservationController::class, 'cancel']);
    Route::post('/reservations/{reservation}/ready', [AdminReservationController::class, 'markAsReady']);
});

// Books routes
Route::middleware(['auth:api'])->group(function () {
    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/search', [BookController::class, 'search']);
    
    // Admin only routes
    Route::middleware(['admin'])->group(function () {
        Route::post('/books', [BookController::class, 'store']);
        Route::put('/books/{book}', [BookController::class, 'update']);
        Route::delete('/books/{book}', [BookController::class, 'destroy']);
        Route::put('/books/{book}/copies', [BookController::class, 'updateCopies']);
    });
});

// Reservation routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/books/{book}/reserve', [ReservationController::class, 'store']);
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);
});

// User routes
Route::middleware(['auth:api'])->group(function () {
    Route::get('/user/reservations', [ReservationController::class, 'index']);
    Route::post('/user/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);
});

// Book routes
Route::middleware(['auth:api'])->group(function () {
    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/search', [BookController::class, 'search']);
});
