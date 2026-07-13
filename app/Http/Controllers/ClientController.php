<?php

namespace App\Http\Controllers;

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

    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'nullable',
            'email' => 'nullable|email',
            'tele' => 'nullable'
        ]);

        $id = DB::table('clients')->insertGetId([
            'name' => $request->name,
            'address' => $request->address,
            'email' => $request->email,
            'tele' => $request->tele
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Client created successfully',
            'client_id' => $id
        ], 201);
    }


    public function show($id)
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

  
    public function update(Request $request, $id)
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
                'tele' => $request->tele
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Client updated successfully'
        ]);
    }

    public function destroy($id)
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

    public function sites($id)
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

        $sites = DB::table('sites')
            ->where('id_client', $id)
            ->get();

        return response()->json([
            'success' => true,
            'client' => $client,
            'sites' => $sites,
            'data' => $sites
        ]);
    }
}