<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\VerificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/auth/verify-reset-code', [PasswordResetController::class, 'verifyCode']);
Route::post('/auth/reset-password', [PasswordResetController::class, 'reset']);


Route::middleware('auth:api')->group(function () {
    Route::post('/email/verify', [VerificationController::class, 'verify']);
    Route::post('/email/resend', [VerificationController::class, 'resend']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

 // Profile
 Route::get('/profile', [UserController::class, 'show']);
 Route::put('/profile', [UserController::class, 'update']);
 Route::put('/profile/password', [UserController::class, 'updatePassword']);


});

