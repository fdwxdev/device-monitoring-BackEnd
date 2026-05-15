<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{

    public function index()
    {
        $clients = DB::table('clients')->get();

        return response()->json([
            'success' => true,
            'data' => $clients
        ]);
    }

    /*
    | CREATE CLIENT*/

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required',
            'address' => 'nullable',
            'email' => 'nullable|email',
            'phone' => 'nullable'
        ]);

        $id = DB::table('clients')->insertGetId([

            'name' => $request->name,
            'address' => $request->address,
            'email' => $request->email,
            'phone' => $request->phone,
            'created_at' => now(),
            'updated_at' => now()

        ]);

        return response()->json([
            'success' => true,
            'message' => 'Client created successfully',
            'client_id' => $id
        ], 201);
    }

    /*
    | SHOW ONE CLIENT
    */

    public function show(string $id)
    {

        $client = DB::table('clients')
            ->where('id', $id)
            ->first();

        if (!$client) {

            return response()->json([
                'success' => false,
                'message' => 'Client not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $client
        ]);
    }

    /*UPDATE CLIENT*/

    public function update(Request $request, string $id)
    {

        $client = DB::table('clients')
            ->where('id', $id)
            ->first();

        if (!$client) {

            return response()->json([
                'success' => false,
                'message' => 'Client not found'
            ], 404);
        }

        DB::table('clients')
            ->where('id', $id)
            ->update([

                'name' => $request->name,
                'address' => $request->address,
                'email' => $request->email,
                'phone' => $request->phone,
                'updated_at' => now()

            ]);

        return response()->json([
            'success' => true,
            'message' => 'Client updated successfully'
        ]);
    }

    /*DELETE CLIENT */

    public function destroy(string $id)
    {

        $client = DB::table('clients')
            ->where('id', $id)
            ->first();

        if (!$client) {

            return response()->json([
                'success' => false,
                'message' => 'Client not found'
            ], 404);
        }

        DB::table('clients')
            ->where('id', $id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client deleted successfully'
        ]);
    }

}

