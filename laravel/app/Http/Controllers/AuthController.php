<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    


    public function signup(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users,username|min:4|max:60',
            'password' => 'required|min:5|max:10',
        ]);

        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => 'player', 
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
