<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;   // 
use Illuminate\Support\Facades\Hash; //  
use Illuminate\Support\Facades\Log;

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

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email non trouvé dans la base de données: ' . $request->email
            ], 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mot de passe incorrect pour cet utilisateur',
                'debug_received_password' => $request->password,
                'debug_hash_in_db' => $user->password
            ], 401);
        }

        $token = $user->remember_token ?: bin2hex(random_bytes(32));

        DB::table('users')->where('id', $user->id)->update(['remember_token' => $token]);

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