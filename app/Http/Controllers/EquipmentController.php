<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EquipmentController extends Controller
{
    
    public function index(Request $request)
    {
        $clientId = $request->user()->client_id;

        $equipments = DB::table('equipement')
            ->join('devices', 'equipement.id_device', '=', 'devices.id')
            ->join('sites', 'devices.id_site', '=', 'sites.id')
            ->where('sites.id_client', $clientId)
            ->select('equipement.*', 'devices.name as device_name', 'sites.name as site_name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $equipments
        ], 200);
    }

  

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

    $id = DB::table('equipement')->insertGetId([
        'name' => $request->name,
        'id_device' => $request->id_device,
    ]);

    $equipment = DB::table('equipement')
        ->where('id', $id)
        ->first();

    return response()->json([
        'success' => true,
        'data' => $equipment
    ], 201);
}
    
    public function show($id)
    {
        $equipment = DB::table('equipement')->where('id', $id)->first();

        if (!$equipment) {
            return response()->json(['message' => 'Equipment not found'], 404);
        }

        return response()->json($equipment, 200);
    }

    
    public function update(Request $request, $id)
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

        $updated = DB::table('equipement')->where('id', $id)->update([
            'name'      => $request->name,
            'id_device' => $request->id_device,
        ]);

        if (!$updated) {
            return response()->json(['message' => 'Equipment not found or no changes made'], 404);
        }

        $equipment = DB::table('equipement')->where('id', $id)->first();
        return response()->json($equipment, 200);
    }

 
    public function destroy($id)
    {
        $deleted = DB::table('equipement')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Equipment not found'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Equipment deleted successfully'
        ], 200);
    }
}