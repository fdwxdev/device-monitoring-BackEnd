<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SiteController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();

        if (strtolower($user->role) === 'superadmin') {
            $sitesQuery = DB::table('sites');
        } else {
            $sitesQuery = DB::table('sites')->where('id_client', $user->client_id);
        }

        $sites = $sitesQuery->get()->map(function ($site) {
            $devicesCount = DB::table('devices')->where('id_site', $site->id)->count();

            $deviceIds = DB::table('devices')->where('id_site', $site->id)->pluck('id');
            $equipmentsCount = 0;
            $sensorsCount = 0;

            if ($deviceIds->isNotEmpty()) {
                $equipmentsCount = DB::table('equipement')->whereIn('id_device', $deviceIds)->count();
                $equipmentIds = DB::table('equipement')->whereIn('id_device', $deviceIds)->pluck('id');

                if ($equipmentIds->isNotEmpty()) {
                    $sensorsCount = DB::table('sensors')->whereIn('id_equipement', $equipmentIds)->count();
                }
            }

            $site->devices_count = $devicesCount;
            $site->equipments_count = $equipmentsCount;
            $site->sensors_count = $sensorsCount;

            return $site;
        });

        return response()->json([
            'success' => true,
            'data'    => $sites
        ], 200);
    }

    public function show($id)
    {
        $site = DB::table('sites')->where('id', $id)->first();

        if (!$site) {
            return response()->json(['message' => 'Site not found'], 404);
        }

        $client = DB::table('clients')->where('id', $site->id_client)->first();
        $site->client_name = $client ? $client->name : 'N/A';

        $devices = DB::table('devices')->where('id_site', $id)->get();

        $deviceIds = $devices->pluck('id');
        $equipments = [];
        $sensors = [];

        if ($deviceIds->isNotEmpty()) {
            $equipments = DB::table('equipement')
                ->whereIn('id_device', $deviceIds)
                ->get();

            $equipmentIds = collect($equipments)->pluck('id');
            if ($equipmentIds->isNotEmpty()) {
                $sensors = DB::table('sensors')
                    ->whereIn('id_equipement', $equipmentIds)
                    ->get();
            }
        }

        return response()->json([
            'success' => true,
            'site' => $site,
            'devices' => $devices,
            'equipments' => $equipments,
            'sensors' => $sensors
        ], 200);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string',
            'adress'    => 'required|string',
            'city'      => 'required|string',
            'id_client' => 'required|integer',
            'image'     => 'nullable|image|mimes:jpeg,png,jpg|max:2048',  
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            //   save pic in  storage/app/public/sites
            $imagePath = $request->file('image')->store('sites', 'public');
        }

        $id = DB::table('sites')->insertGetId([
            'name'      => $request->name,
            'adress'    => $request->adress,
            'city'      => $request->city,
            'id_client' => $request->id_client,
            'image'     => $imagePath, //   ( sites/xyz.jpg) save chemin-------
        ]);

        $site = DB::table('sites')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Site created successfully',
            'data'    => $site
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $site = DB::table('sites')->where('id', $id)->first();
        if (!$site) return response()->json(['message' => 'Site not found'], 404);

        $updateData = [
            'name'      => $request->name ?? $site->name,
            'adress'    => $request->adress ?? $site->adress,
            'city'      => $request->city ?? $site->city,
            'id_client' => $request->id_client ?? $site->id_client,
        ];

        if ($request->hasFile('image')) {
            $updateData['image'] = $request->file('image')->store('sites', 'public');
        }

        DB::table('sites')->where('id', $id)->update($updateData);

        $updatedSite = DB::table('sites')->where('id', $id)->first();
        return response()->json($updatedSite, 200);
    }


    public function destroy($id)
    {
        // Supprimer en cascade pour éviter les erreurs de clés étrangères
        $deviceIds = DB::table('devices')->where('id_site', $id)->pluck('id');
        if ($deviceIds->isNotEmpty()) {
            $equipmentIds = DB::table('equipement')->whereIn('id_device', $deviceIds)->pluck('id');
            if ($equipmentIds->isNotEmpty()) {
                DB::table('sensors')->whereIn('id_equipement', $equipmentIds)->delete();
                DB::table('equipement')->whereIn('id_device', $deviceIds)->delete();
            }
            DB::table('devices')->where('id_site', $id)->delete();
        }

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
