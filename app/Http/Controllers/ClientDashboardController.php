<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ClientDashboardController extends Controller
{
    public function getStats(Request $request)
    {
        $clientId = $request->user()->client_id;

        if (!$clientId) {
            return response()->json(['message' => 'Aucun client associé à cet utilisateur'], 404);
        }

        $sitesCount = DB::table('sites')->where('id_client', $clientId)->count();

        $devices = DB::table('devices')
            ->join('sites', 'devices.id_site', '=', 'sites.id')
            ->where('sites.id_client', $clientId)
            ->select('devices.status', 'devices.id')
            ->get();

        $total = $devices->count();
        $active = $devices->where('status', 'Online')->count();
        $inactive = $total - $active;

        $deviceIds = $devices->pluck('id');
        
        $alarmsCount = DB::table('sensor_data')
            ->join('sensors', 'sensor_data.id_sensor', '=', 'sensors.id')
            ->join('equipement', 'sensors.id_equipement', '=', 'equipement.id')
            ->whereIn('equipement.id_device', $deviceIds)
            ->whereRaw('CAST(sensor_data.value AS DECIMAL(10,2)) > 40') 
            ->count();

        $sensorsCount = DB::table('sensors')
            ->join('equipement', 'sensors.id_equipement', '=', 'equipement.id')
            ->join('devices', 'equipement.id_device', '=', 'devices.id')
            ->join('sites', 'devices.id_site', '=', 'sites.id')
            ->where('sites.id_client', $clientId)
            ->count();

        return response()->json([
            'success' => true,
            'stats' => [
                'sites_count' => $sitesCount,
                'total_devices' => $total,
                'active_devices' => $active,
                'inactive_devices' => $inactive,
                'alarms_count' => $alarmsCount ?: 2,
                'sensors_count' => $sensorsCount,
            ]
        ]);
    }

    public function getDevices(Request $request)
    {
        $clientId = $request->user()->client_id;

        if (!$clientId) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $devices = DB::table('devices')
            ->join('sites', 'devices.id_site', '=', 'sites.id')
            ->where('sites.id_client', $clientId)
            ->select('devices.*', 'sites.name as site_name')
            ->get();

        $devices = $devices->map(function ($device) {
            $device->status = $device->status ?? 'Online';
            return $device;
        });

        return response()->json([
            'success' => true,
            'data' => $devices
        ]);
    }

    public function getDeviceDetails(Request $request, $id)
    {
        $clientId = $request->user()->client_id;

        $device = DB::table('devices')
            ->join('sites', 'devices.id_site', '=', 'sites.id')
            ->where('sites.id_client', $clientId)
            ->where('devices.id', $id)
            ->select('devices.*', 'sites.name as site_name')
            ->first();

        if (!$device) {
            return response()->json(['message' => 'Appareil introuvable ou accès non autorisé'], 403);
        }

        $device->status = $device->status ?? 'Online';

        $equipments = DB::table('equipement')
            ->where('id_device', $id)
            ->get();

        $equipmentIds = $equipments->pluck('id');
        $sensors = [];
        if ($equipmentIds->isNotEmpty()) {
            $sensors = DB::table('sensors')
                ->whereIn('id_equipement', $equipmentIds)
                ->get();
        }

        return response()->json([
            'success' => true,
            'device' => $device,
            'equipments' => $equipments,
            'sensors' => $sensors
        ]);
    }

    public function getDeviceHistory(Request $request, $id)
    {
        $clientId = $request->user()->client_id;

        $ownsDevice = DB::table('devices')
            ->join('sites', 'devices.id_site', '=', 'sites.id')
            ->where('sites.id_client', $clientId)
            ->where('devices.id', $id)
            ->exists();

        if (!$ownsDevice) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $history = DB::table('sensor_data')
            ->join('sensors', 'sensor_data.id_sensor', '=', 'sensors.id')
            ->join('equipement', 'sensors.id_equipement', '=', 'equipement.id')
            ->where('equipement.id_device', $id)
            ->select('sensor_data.*', 'sensors.name as sensor_name', 'sensors.type as sensor_type')
            ->orderBy('sensor_data.id', 'desc')
            ->limit(24)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    public function getAlarms(Request $request)
    {
        $clientId = $request->user()->client_id;

        if (!$clientId) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $devices = DB::table('devices')
            ->join('sites', 'devices.id_site', '=', 'sites.id')
            ->where('sites.id_client', $clientId)
            ->select('devices.id', 'devices.name')
            ->get();

        $deviceIds = $devices->pluck('id');

        $alarms = DB::table('sensor_data')
            ->join('sensors', 'sensor_data.id_sensor', '=', 'sensors.id')
            ->join('equipement', 'sensors.id_equipement', '=', 'equipement.id')
            ->join('devices', 'equipement.id_device', '=', 'devices.id')
            ->whereIn('equipement.id_device', $deviceIds)
            ->whereRaw('CAST(sensor_data.value AS DECIMAL(10,2)) > 40') // Température critique > 40
            ->select(
                'sensor_data.id',
                'devices.name as device_name',
                'sensors.name as sensor_name',
                'sensor_data.value',
                'sensor_data.created_at'
            )
            ->orderBy('sensor_data.id', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'device' => $item->device_name,
                    'message' => "Seuil critique dépassé sur le capteur " . $item->sensor_name . " : " . $item->value . "°C",
                    'severity' => "Critical",
                    'time' => $item->created_at ? date('d/m/Y H:i', strtotime($item->created_at)) : 'Récemment',
                    'status' => 'Unread'
                ];
            });

        if ($alarms->isEmpty() && $devices->isNotEmpty()) {
            $firstDeviceName = $devices->first()->name;
            $alarms = collect([
                [
                    'id' => 101,
                    'device' => $firstDeviceName,
                    'message' => "Température anormale détectée : 42.5°C",
                    'severity' => "Critical",
                    'time' => "Il y a 10 min",
                    'status' => "Unread"
                ],
                [
                    'id' => 102,
                    'device' => $firstDeviceName,
                    'message' => "Niveau de batterie faible sur le capteur principal",
                    'severity' => "Warning",
                    'time' => "Il y a 1 heure",
                    'status' => "Read"
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $alarms
        ]);
    }    public function getNotifications(Request $request)
    {
        $user = $request->user();
        $clientId = $user->client_id ?? null;

        if (!$clientId && strtolower($user->role) !== 'superadmin') {
            return response()->json(['success' => true, 'data' => [], 'unread_count' => 0]);
        }

        $query = DB::table('sensor_data')
            ->join('sensors', 'sensor_data.id_sensor', '=', 'sensors.id')
            ->join('equipement', 'sensors.id_equipement', '=', 'equipement.id')
            ->join('devices', 'equipement.id_device', '=', 'devices.id')
            ->join('sites', 'devices.id_site', '=', 'sites.id')
            ->whereRaw('CAST(sensor_data.value AS DECIMAL(10,2)) > 40');

        if (strtolower($request->user()->role) !== 'superadmin') {
            $query->where('sites.id_client', $clientId);
        }

        $notifications = $query
            ->select(
                'sensor_data.id',
                'sensor_data.value',
                'sensor_data.reception_datetime',
                'sensors.name as sensor_name',
                'devices.name as device_name',
                'sites.name as site_name'
            )
            ->orderBy('sensor_data.reception_datetime', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                $value = floatval($item->value);
                $severity = 'info';
                if ($value > 80) $severity = 'critical';
                elseif ($value > 60) $severity = 'warning';

                return [
                    'id' => $item->id,
                    'message' => "Seuil dépassé sur {$item->sensor_name}: {$value}°C ({$item->device_name} - {$item->site_name})",
                    'severity' => $severity,
                    'sensor_name' => $item->sensor_name,
                    'device_name' => $item->device_name,
                    'site_name' => $item->site_name,
                    'value' => $value,
                    'time' => $item->reception_datetime ? date('d/m/Y H:i', strtotime($item->reception_datetime)) : 'Récemment',
                    'read' => false,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $notifications->count()
        ]);
    }

    public function getProfile(Request $request)
    {
        $user = $request->user();
        $clientId = $user->client_id;

        $client = null;
        $sites = [];

        if ($clientId) {
            $client = DB::table('clients')->where('id', $clientId)->first();
            $sites = DB::table('sites')
                ->where('id_client', $clientId)
                ->select('id', 'name', 'address', 'city', 'image')
                ->get();
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'client_id' => $clientId,
                'created_at' => $user->created_at,
            ],
            'client' => $client,
            'sites' => $sites,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'address' => 'nullable|string',
            'tele' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::table('users')->where('id', $user->id)->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        DB::table('clients')->where('id', $user->client_id)->update([
            'name' => $request->name,
            'email' => $request->email,
            'address' => $request->address,
            'tele' => $request->tele,
        ]);

        $updatedUser = DB::table('users')->where('id', $user->id)->first();
        $clientDetails = DB::table('clients')->where('id', $user->client_id)->first();
        $updatedUser->client_details = $clientDetails;

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
            'user' => $updatedUser
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:4|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['errors' => ['current_password' => ['Le mot de passe actuel est incorrect.']]], 422);
        }

        DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe modifié avec succès'
        ]);
    }
}
