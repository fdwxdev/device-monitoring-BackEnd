<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EquipmentController extends Controller
{
    /**
     *
     */
    public function index(Request $request)
    {
        $clientId = $request->user()->client_id;

        $equipments = DB::table('equipments')
            ->join('devices', 'equipments.id_device', '=', 'devices.id')
            ->join('sites', 'devices.id_site', '=', 'sites.id')
            ->where('sites.id_client', $clientId)
            ->select('equipments.*', 'devices.name as device_name', 'sites.name as site_name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $equipments
        ], 200);
    }

    /**
     *
     */

public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'id_device' => 'required|integer'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    $id = DB::table('equipments')->insertGetId([
        'name' => $request->name,
        'id_device' => $request->id_device,
    ]);

    $equipment = DB::table('equipments')
        ->where('id', $id)
        ->first();

    return response()->json([
        'success' => true,
        'data' => $equipment
    ], 201);
}
    /**
     
     */
    public function show($id)
    {
        $site = DB::table('sites')->where('id', $id)->first();

        if (!$site) {
            return response()->json(['message' => 'Site not found'], 404);
        }

        return response()->json($site, 200);
    }

    /**
     
     */
    public function update(Request $request, $id)
    {
        $updated = DB::table('sites')->where('id', $id)->update([
            'name'      => $request->name,
            'adress'    => $request->adress,
            'city'      => $request->city,
            'id_client' => $request->id_client,
        ]);

        if (!$updated) {
            return response()->json(['message' => 'Site not found or no changes made'], 404);
        }

        $site = DB::table('sites')->where('id', $id)->first();
        return response()->json($site, 200);
    }

    /**
     * DELETE /api/sites/{id}
     * 
     */
    public function destroy($id)
    {
        $deleted = DB::table('sites')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Site not found'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Site deleted successfully'
        ], 200);
    }
}