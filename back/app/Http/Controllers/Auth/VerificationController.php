<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationCode;
use App\Notifications\VerifyEmailNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VerificationController extends Controller
{
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        $user = $request->user();
        $code = $request->code;

        $verificationCode = VerificationCode::where('user_id', $user->id)
            ->where('code', $code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$verificationCode) {
            return response()->json([
                'message' => 'Invalid or expired verification code'
            ], 400);
        }

        $user->email_verified_at = now();
        $user->save();

        // Delete all verification codes for this user
        VerificationCode::where('user_id', $user->id)->delete();

        return response()->json([
            'message' => 'Email verified successfully'
        ]);
    }

    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified'
            ]);
        }

        // Delete old codes
        VerificationCode::where('user_id', $user->id)->delete();

        // Generate new code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Save new code
        VerificationCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(10)
        ]);

        // Send notification
        $user->notify(new VerifyEmailNotification($code));

        return response()->json([
            'message' => 'Verification code sent'
        ]);
    }
} 