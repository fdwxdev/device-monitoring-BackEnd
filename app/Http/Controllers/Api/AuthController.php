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

        // Query directly with DB::table
        $user = DB::table('users')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email or password incorrect'
            ], 401);
        }

        // Retrieve or generate a stable remember_token as the API token
        $token = $user->remember_token;
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            DB::table('users')->where('id', $user->id)->update(['remember_token' => $token]);
            $user->remember_token = $token;
        }

        return response()->json([
            'message' => 'Login success',
            'user' => $user,
            'access_token' => $token,
            'role' => $user->role
        ]);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        return response()->json([
            'message' => 'Logout successful'
        ]);
    }

    // PROFILE
    public function profile(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }
}