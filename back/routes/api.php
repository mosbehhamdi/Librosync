<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\VerificationController;
use App\Mail\TestMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
// Test routes
Route::get('/test', fn() => response()->json(['message' => 'API is working']));
Route::get('/ping', fn() => response()->json(['message' => 'pong']));
Route::get('/test-mail', function () {
    try {
        Mail::to('mesbahhamdidsi@gmail.com')->send(new TestMail());
        return response()->json(['message' => 'Test email sent successfully!']);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});

// Public auth routes with rate limiting
Route::middleware('throttle:6,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:6,1')
        ->name('password.reset');
});

// Protected routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    // Protected routes
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('user', [AuthController::class, 'user']);
    });
});

// Email Verification Routes
Route::middleware(['auth:api'])->group(function () {
    Route::post('/email/verify', [VerificationController::class, 'verify'])
        ->name('verification.verify');
    Route::post('/email/resend', [VerificationController::class, 'resend'])
        ->name('verification.resend');
});

// Password Reset Routes
Route::post('forgot-password', [PasswordResetController::class, 'forgotPassword'])
    ->middleware('throttle:6,1')
    ->name('password.email');

Route::post('verify-reset-code', [PasswordResetController::class, 'verifyCode'])
    ->middleware('throttle:6,1')
    ->name('password.code.verify');

Route::post('reset-password', [PasswordResetController::class, 'reset'])
    ->middleware('throttle:6,1')
    ->name('password.update');
