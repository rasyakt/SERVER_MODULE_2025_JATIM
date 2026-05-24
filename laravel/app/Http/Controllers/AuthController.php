<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Sign Up / Register a new user.
     */
    public function signup(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users,username|min:4|max:60',
            'password' => 'required|min:5|max:10',
        ]);

        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => 'player', // Default role for standard signup
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'username' => $user->username,
                'role' => $user->role
            ]
        ], 201);
    }

    /**
     * Sign In / Login an existing user.
     */
    public function signin(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Wrong username or password'
            ], 401);
        }

        // Update last login timestamp
        $user->update([
            'last_login_at' => now()
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'username' => $user->username,
                'role' => $user->role
            ]
        ], 200);
    }

    /**
     * Sign Out / Logout the authenticated user.
     */
    public function signout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'status' => 'success'
        ], 200);
    }
}
