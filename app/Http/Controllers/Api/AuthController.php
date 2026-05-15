<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = DB::table('users')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email or password incorrect'
            ], 401);
        }

        // simple token (without Sanctum)
        $token = bin2hex(random_bytes(32));

        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'remember_token' => $token
            ]);

        return response()->json([
            'message' => 'Login success',
            'user' => $user,
            'token' => $token
        ]);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json(['message' => 'No token provided'], 401);
        }

        DB::table('users')
            ->where('remember_token', $token)
            ->update([
                'remember_token' => null
            ]);

        return response()->json([
            'message' => 'Logout successful'
        ]);
    }
}