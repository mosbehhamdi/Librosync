<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function users(): JsonResponse
    {
        $users = User::where('is_admin', false)->get();
        return response()->json(['users' => $users]);
    }

    public function dashboard(): JsonResponse
    {
        $stats = [
            'total_users' => User::count(),
            'verified_users' => User::whereNotNull('email_verified_at')->count(),
            'unverified_users' => User::whereNull('email_verified_at')->count(),
        ];
        
        return response()->json($stats);
    }

    public function deleteUser(User $user): JsonResponse
    {
        if ($user->is_admin) {
            return response()->json(['message' => 'Cannot delete admin users'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'is_admin' => 'sometimes|boolean'
        ]);

        $user->update($validated);
        return response()->json(['user' => $user]);
    }
} 