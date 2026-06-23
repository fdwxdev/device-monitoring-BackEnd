<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    
    public function index(Request $request)
    {
        $user = $request->user();

        if (strtolower($user->role) === 'superadmin') {
            $devices = DB::table('devices')
                ->join('sites', 'devices.id_site', '=', 'sites.id')
                ->select('devices.*', 'sites.name as site_name')
                ->get();
        } else {
            $devices = DB::table('devices')
                ->join('sites', 'devices.id_site', '=', 'sites.id')
                ->where('sites.id_client', $user->client_id)
                ->select('devices.*', 'sites.name as site_name')
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $devices
        ], 200);
    }

                     
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'id_site' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = $request->user();

        if (strtolower($user->role) !== 'superadmin') {
            $siteOwnedByClient = DB::table('sites')
                ->where('id', $request->id_site)
                ->where('id_client', $user->client_id)
                ->exists();

            if (!$siteOwnedByClient) {
                return response()->json(['message' => 'Unauthorized site'], 403);
            }
        }

        $deviceId = DB::table('devices')->insertGetId([
            'name' => $request->name,
            'id_site' => $request->id_site,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Device created successfully',
            'id' => $deviceId
        ], 201);
    }

   
    public function show(Request $request, $id)
    {
        $user = $request->user();

        if (strtolower($user->role) === 'superadmin') {
            $device = DB::table('devices')
                ->join('sites', 'devices.id_site', '=', 'sites.id')
                ->where('devices.id', $id)
                ->select('devices.*', 'sites.name as site_name')
                ->first();
        } else {
            $device = DB::table('devices')
                ->join('sites', 'devices.id_site', '=', 'sites.id')
                ->where('sites.id_client', $user->client_id)
                ->where('devices.id', $id)
                ->select('devices.*', 'sites.name as site_name')
                ->first();
        }

        if (!$device) {
            return response()->json(['message' => 'Device not found or unauthorized'], 404);
        }

        return response()->json($device, 200);
    }


    public function update(Request $request, $id)
    {
        $user = $request->user();

        if (strtolower($user->role) === 'superadmin') {
            $device = DB::table('devices')->where('id', $id)->first();
        } else {
            $device = DB::table('devices')
                ->join('sites', 'devices.id_site', '=', 'sites.id')
                ->where('sites.id_client', $user->client_id)
                ->where('devices.id', $id)
                ->select('devices.*')
                ->first();
        }

        if (!$device) {
            return response()->json(['message' => 'Device not found or unauthorized'], 404);
        }

        DB::table('devices')->where('id', $id)->update([
            'name' => $request->name ?? $device->name,
            'id_site' => $request->id_site ?? $device->id_site,
        ]);

        return response()->json(['message' => 'Device updated successfully'], 200);
    }

    
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if (strtolower($user->role) !== 'superadmin') {
            $exists = DB::table('devices')
                ->join('sites', 'devices.id_site', '=', 'sites.id')
                ->where('sites.id_client', $user->client_id)
                ->where('devices.id', $id)
                ->exists();

            if (!$exists) {
                return response()->json(['message' => 'Unauthorized action'], 403);
            }
        }

        DB::table('devices')->where('id', $id)->delete();

        return response()->json(['message' => 'Device deleted successfully'], 200);
    }

  
    public function getEquipments(Request $request)
    {
        $user = $request->user();

        if (strtolower($user->role) === 'superadmin') {
            $equipments = DB::table('equipement')
                ->join('devices', 'equipement.id_device', '=', 'devices.id')
                ->select('equipement.*', 'devices.name as device_name')
                ->get();
        } else {
            $equipments = DB::table('equipement')
                ->join('devices', 'equipement.id_device', '=', 'devices.id')
                ->join('sites', 'devices.id_site', '=', 'sites.id')
                ->where('sites.id_client', $user->client_id)
                ->select('equipement.*', 'devices.name as device_name')
                ->get();
        }

        return response()->json($equipments, 200);
    }
}
