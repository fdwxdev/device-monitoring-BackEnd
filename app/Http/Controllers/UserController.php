<?php



namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // GET /utilisateurs
    public function index(Request $request)
    {
        $user = $request->user(); // this user come from middleware token.auth

        if (strtolower($user->role) === 'superadmin') {
            $utilisateurs = DB::table('users')
                ->select('id', 'name', 'name as username', 'email', 'role', 'client_id as account_id', 'created_at', 'updated_at')
                ->get();
        } else {
            // Admin Client can see just his users account 
            $utilisateurs = DB::table('users')
                ->select('id', 'name', 'name as username', 'email', 'role', 'client_id as account_id', 'created_at', 'updated_at')
                ->where('client_id', $user->client_id)
                ->get();
        }

        return response()->json($utilisateurs);
    }

    // POST /utilisateurs
    public function store(Request $request)
    {
        $admin = $request->user();

        // Validate data
        $request->validate([
            'username' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'role' => 'required'
        ]);

    
        $clientId = (strtolower($admin->role) === 'superadmin') 
                        ? $request->account_id 
                        : $admin->client_id;

        DB::table('users')->insert([
            'name' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'client_id' => $clientId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Utilisateur créé avec succès'], 201);
    }

}


/*
class UserController extends Controller
{
    
     
    public function index()
    {
         $users = DB::table('users')->get();

        return response()->json($users);
    }


    public function create()
    {
        //
    }

    
    
   public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8',
        'role' => 'required',
        'client_id' => 'nullable|integer'
    ]);

    $id = DB::table('users')->insertGetId([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => $request->role,
        'client_id' => $request->client_id,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    return response()->json([
        'success' => true,
        'message' => 'User created successfully',
        'id' => $id
    ], 201);
}

   
    public function show(string $id)
    {
        $user = DB::table('users')
            ->where('id', $id)
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        return response()->json($user);
    }

    public function edit(string $id)
    {
        //
    }

 
 public function update(Request $request, string $id)
{
    $user = DB::table('users')->where('id', $id)->first();

    if (!$user) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }

    DB::table('users')
        ->where('id', $id)
        ->update([
            'name' => $request->name ?? $user->name,
            'email' => $request->email ?? $user->email,
            'role' => $request->role ?? $user->role,
            'client_id' => $request->client_id ?? $user->client_id,
            'updated_at' => now()
        ]);

    return response()->json([
        'success' => true,
        'message' => 'User updated successfully'
    ]);
}

    public function destroy(string $id)
{
    $user = DB::table('users')->where('id', $id)->first();

    if (!$user) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }

    DB::table('users')
        ->where('id', $id)
        ->delete();

    return response()->json([
        'success' => true,
        'message' => 'User deleted successfully'
    ]);
}
} */
