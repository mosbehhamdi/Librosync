<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\Admin\AdminReservationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/auth/verify-reset-code', [PasswordResetController::class, 'verifyCode']);
Route::post('/auth/reset-password', [PasswordResetController::class, 'reset']);

// Email verification routes
Route::middleware('auth:api')->group(function () {
    Route::post('/email/verify', [VerificationController::class, 'verify']);
    Route::post('/email/resend', [VerificationController::class, 'resend']);
});

// User routes (authenticated)
Route::middleware('auth:api')->group(function () {
    // Auth
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    
    // Books
    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/search', [BookController::class, 'search']);
    Route::get('/books/category/{category}', [BookController::class, 'getByCategory']);
    Route::post('/books/{book}/reserve', [ReservationController::class, 'store']);
    
    // User reservations
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::get('/reservations/expired', [ReservationController::class, 'handleExpiredReservations']);
    Route::get('/reservations/{reservation}/queue-position', [ReservationController::class, 'getQueuePosition']);
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);
    Route::get('/reservations/history', [ReservationController::class, 'history']);
    Route::get('/reservations/statistics', [ReservationController::class, 'statistics']);
    
    // Profile
    Route::get('/profile', [UserController::class, 'show']);
    Route::put('/profile', [UserController::class, 'update']);
    Route::put('/profile/password', [UserController::class, 'updatePassword']);
});

// Admin routes
Route::middleware(['auth:api', 'admin'])->group(function () {
    // Dashboard
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
    
    // Users management
    Route::get('/admin/users', [AdminController::class, 'users']);
    Route::delete('/admin/users/{user}', [AdminController::class, 'deleteUser']);
    
    // Books management
    Route::get('/admin/books', [BookController::class, 'adminIndex']);
    Route::post('/admin/books', [BookController::class, 'store']);
    Route::get('/admin/books/{book}', [BookController::class, 'show']);
    Route::put('/admin/books/{book}', [BookController::class, 'update']);
    Route::delete('/admin/books/{book}', [BookController::class, 'destroy']);
    Route::put('/admin/books/{book}/copies', [BookController::class, 'updateCopies']);
    
    // Reservations management
    Route::get('/admin/reservations', [AdminReservationController::class, 'index']);
    Route::post('/admin/reservations/{reservation}/cancel', [AdminReservationController::class, 'cancel']);
    Route::post('/admin/reservations/{reservation}/ready', [AdminReservationController::class, 'markAsReady']);
    Route::get('/admin/reservations/statistics', [AdminReservationController::class, 'reservationStatistics']);
    // Statistics
    Route::get('/admin/statistics/books', [AdminController::class, 'bookStatistics']);
    Route::get('/admin/statistics/users', [AdminController::class, 'userStatistics']);
    Route::get('/admin/books/search', [BookController::class, 'adminSearch']);
    Route::get('/admin/reservations/history', [AdminReservationController::class, 'history']);
});

// Waitlist routes
Route::middleware('auth:api')->group(function () {
    Route::post('/books/{book}/waitlist', [ReservationController::class, 'joinWaitlist']);
});

Route::get('/books/{book}/queue-position', [ReservationController::class, 'getQueuePosition']);
