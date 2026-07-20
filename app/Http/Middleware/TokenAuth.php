<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = substr($header, 7);

        $user = DB::table('users')
            ->where('remember_token', $token)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // client_id is already in the users table directly

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}