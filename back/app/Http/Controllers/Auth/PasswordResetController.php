<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordResetCode;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRules;

class PasswordResetController extends Controller
{
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        try {
            $user = User::where('email', $request->email)->first();
            
            // Generate a 6-digit code
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store the code
            PasswordResetCode::updateOrCreate(
                ['email' => $user->email],
                [
                    'code' => $code,
                    'expires_at' => now()->addMinutes(60)
                ]
            );

            // Send the notification
            $user->notify(new ResetPasswordNotification($code));

            return response()->json([
                'message' => 'Password reset code sent to your email'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send reset code', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);
            
            return response()->json([
                'message' => 'Failed to send reset code'
            ], 500);
        }
    }

    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRules::min(8)],
            'password_confirmation' => 'required'
        ]);

        try {
            $resetCode = PasswordResetCode::where('email', $request->email)
                ->where('token', $request->token)
                ->where('expires_at', '>', now())
                ->first();

            if (!$resetCode) {
                return response()->json([
                    'message' => 'Invalid or expired reset token'
                ], 400);
            }

            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }

            // Update password
            $user->password = Hash::make($request->password);
            $user->save();

            // Delete the reset code
            $resetCode->delete();

            // Optional: Logout other devices
            // Auth::logoutOtherDevices($request->password);

            return response()->json([
                'message' => 'Password has been reset successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Password reset failed', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);

            return response()->json([
                'message' => 'Failed to reset password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6'
        ]);

        try {
            Log::info('Verifying reset code', [
                'email' => $request->email,
                'code' => $request->code
            ]);

            $resetCode = PasswordResetCode::where('email', $request->email)
                ->where('code', $request->code)
                ->where('expires_at', '>', now())
                ->first();

            if (!$resetCode) {
                Log::warning('Invalid reset code attempt', [
                    'email' => $request->email,
                    'code' => $request->code
                ]);
                
                return response()->json([
                    'message' => 'Invalid or expired code'
                ], 400);
            }

            // Generate a unique token
            $token = \Str::random(60);
            $resetCode->update(['token' => $token]);

            Log::info('Code verified successfully', [
                'email' => $request->email,
                'token' => $token
            ]);

            return response()->json([
                'message' => 'Code verified successfully',
                'token' => $token
            ]);
        } catch (\Exception $e) {
            Log::error('Code verification failed', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);
            
            return response()->json([
                'message' => 'Verification failed'
            ], 500);
        }
    }
}
