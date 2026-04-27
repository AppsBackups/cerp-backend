<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

    public function logout()
    {
        auth('sanctum')->user()->tokens()->delete();
        return response()->json(['message' => 'logout successfully'], 200);
    }


    public function create(Request $request)
{
    $request->validate([
        'name' => 'string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:6'
    ]);

    $userData = $request->only(['name', 'email', 'password']);
    $userData['password'] = bcrypt($userData['password']);
    $userData['role_id'] = 1; // Assuming 2 is for regular users

    try {
        $user = User::create($userData);
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'User registered successfully'
        ], 201);

    } catch (\Exception $error) {
        return response()->json([
            'error' => 'Registration failed',
            'message' => $error->getMessage()
        ], 500);
    }
}

    public function getCurrentUser()
    {
        return auth()->user();
    }


    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if ($user && Auth::attempt($credentials)) {
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token
            ], 200);
        }
        return response()->json(['error' => 'Credentials do not match our record'], 401);
    }
}
