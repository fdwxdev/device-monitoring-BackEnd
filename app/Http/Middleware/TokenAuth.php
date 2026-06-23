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

        $user = DB::table('users')->where('remember_token', $token)->first();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Récupérer le client_id depuis la table clients
        $clientId = DB::table('clients')->where('id_user', $user->id)->value('id');
        $user->client_id = $clientId;

        // Enregistrer l'utilisateur dans le résolveur de requête Laravel pour pouvoir utiliser $request->user()
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}
