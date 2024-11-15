<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationCode;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Generate verification code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Save verification code
        VerificationCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(10)
        ]);

        // Send verification email with code
        $user->notify(new VerifyEmailNotification($code));

        $token = Auth::guard('api')->login($user);

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully. Please verify your email.',
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ],
            'verified' => false
        ]);
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            $credentials = $request->only('email', 'password');
            $token = Auth::guard('api')->attempt($credentials);

            if (!$token) {
                return response()->json([
                    'status' => 'error',
                    'toast' => [
                        'message' => 'auth.login.invalid_credentials',
                        'color' => 'danger'
                    ]
                ], 401);
            }

            $user = Auth::guard('api')->user();

            return response()->json([
                'status' => 'success',
                'message' => 'Logged in successfully',
                'user' => $user,
                'authorization' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout()
    {
        Auth::guard('api')->logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::guard('api')->user(),
            'authorization' => [
                'token' => Auth::guard('api')->refresh(),
                'type' => 'bearer',
            ]
        ]);
    }

    public function user()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::guard('api')->user(),
        ]);
    }
}
