<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SensorDataController extends Controller
{
    /**
     * GET /api/sensor-data
     * Jib ga3 sensor_data dial client li mconnecté
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = DB::table('sensor_data')
            ->join('sensors', 'sensor_data.id_sensor', '=', 'sensors.id')
            ->join('equipement', 'sensors.id_equipement', '=', 'equipement.id')
            ->join('devices', 'equipement.id_device', '=', 'devices.id')
            ->join('sites', 'devices.id_site', '=', 'sites.id')
            ->select(
                'sensor_data.*',
                'sensors.name as sensor_name',
                'sensors.description as sensor_description',
                'equipement.name as equipment_name',
                'devices.name as device_name',
                'sites.name as site_name'
            )
            ->orderBy('sensor_data.reception_datetime', 'desc');

        if (strtolower($user->role) !== 'superadmin') {
            $query->where('sites.id_client', $user->client_id);
        }

        $sensorData = $query->get();

        return response()->json([
            'success' => true,
            'data' => $sensorData
        ], 200);
    }

    /**
     * GET /api/sensor-data/{id}
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $query = DB::table('sensor_data')
            ->join('sensors', 'sensor_data.id_sensor', '=', 'sensors.id')
            ->join('equipement', 'sensors.id_equipement', '=', 'equipement.id')
            ->join('devices', 'equipement.id_device', '=', 'devices.id')
            ->join('sites', 'devices.id_site', '=', 'sites.id')
            ->where('sensor_data.id', $id)
            ->select(
                'sensor_data.*',
                'sensors.name as sensor_name',
                'sensors.description as sensor_description',
                'equipement.name as equipment_name',
                'devices.name as device_name',
                'sites.name as site_name'
            );

        if (strtolower($user->role) !== 'superadmin') {
            $query->where('sites.id_client', $user->client_id);
        }

        $data = $query->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Sensor data not found or unauthorized'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }

    public function getAlerts(Request $request)
    {
        $user = $request->user();
        $threshold = $request->input('threshold', 50);

        $query = DB::table('sensor_data')
            ->join('sensors', 'sensor_data.id_sensor', '=', 'sensors.id')
            ->join('equipement', 'sensors.id_equipement', '=', 'equipement.id')
            ->join('devices', 'equipement.id_device', '=', 'devices.id')
            ->join('sites', 'devices.id_site', '=', 'sites.id')
            ->where('sensor_data.value', '>', $threshold)
            ->select(
                'sensor_data.*',
                'sensors.name as sensor_name',
                'sensors.description as sensor_description',
                'equipement.name as equipment_name',
                'devices.name as device_name',
                'sites.name as site_name'
            )
            ->orderBy('sensor_data.reception_datetime', 'desc');

        if (strtolower($user->role) !== 'superadmin') {
            $query->where('sites.id_client', $user->client_id);
        }

        $alerts = $query->get();

        $alerts = $alerts->map(function ($alert) use ($threshold) {
            $value = floatval($alert->value);
            $severity = 'Info';

            if ($value > $threshold * 2) {
                $severity = 'Critical';
            } elseif ($value > $threshold * 1.5) {
                $severity = 'Warning';
            }

            $alert->severity = $severity;
            $alert->message = "Valeur élevée détectée: {$value} (Seuil: {$threshold})";
            return $alert;
        });

        return response()->json([
            'success' => true,
            'data' => $alerts
        ], 200);
    }

    /**
     * GET /api/sensors/{id}/data
     * Jib sensor_data dial sensor wa7ed
     */
    public function getBySensor(Request $request, $sensorId)
    {
        $user = $request->user();

        $query = DB::table('sensor_data')
            ->join('sensors', 'sensor_data.id_sensor', '=', 'sensors.id')
            ->join('equipement', 'sensors.id_equipement', '=', 'equipement.id')
            ->join('devices', 'equipement.id_device', '=', 'devices.id')
            ->join('sites', 'devices.id_site', '=', 'sites.id')
            ->where('sensor_data.id_sensor', $sensorId)
            ->select(
                'sensor_data.*',
                'sensors.name as sensor_name',
                'equipement.name as equipment_name',
                'devices.name as device_name',
                'sites.name as site_name'
            )
            ->orderBy('sensor_data.reception_datetime', 'desc');

        if (strtolower($user->role) !== 'superadmin') {
            $query->where('sites.id_client', $user->client_id);
        }

        $data = $query->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }

    /**
     * POST /api/sensor-data
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|numeric',
            'id_sensor' => 'required|integer|exists:sensors,id',
            'mesure_datetime' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        $user = $request->user();

        // Vérifier authorization
        if (strtolower($user->role) !== 'superadmin') {
            $sensorOwned = DB::table('sensors')
                ->join('equipement', 'sensors.id_equipement', '=', 'equipement.id')
                ->join('devices', 'equipement.id_device', '=', 'devices.id')
                ->join('sites', 'devices.id_site', '=', 'sites.id')
                ->where('sensors.id', $request->id_sensor)
                ->where('sites.id_client', $user->client_id)
                ->exists();

            if (!$sensorOwned) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized sensor'
                ], 403);
            }
        }

        try {
            $dataId = DB::table('sensor_data')->insertGetId([
                'value' => $request->value,
                'id_sensor' => $request->id_sensor,
                'mesure_datetime' => $request->mesure_datetime ?? now(),
                'reception_datetime' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sensor data created successfully',
                'id' => $dataId
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/sensor-data/{id}
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        if (strtolower($user->role) === 'superadmin') {
            $data = DB::table('sensor_data')->where('id', $id)->first();
        } else {
            $data = DB::table('sensor_data')
                ->join('sensors', 'sensor_data.id_sensor', '=', 'sensors.id')
                ->join('equipement', 'sensors.id_equipement', '=', 'equipement.id')
                ->join('devices', 'equipement.id_device', '=', 'devices.id')
                ->join('sites', 'devices.id_site', '=', 'sites.id')
                ->where('sensor_data.id', $id)
                ->where('sites.id_client', $user->client_id)
                ->select('sensor_data.*')
                ->first();
        }

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Sensor data not found or unauthorized'
            ], 404);
        }

        DB::table('sensor_data')->where('id', $id)->update([
            'value' => $request->value ?? $data->value,
            'id_sensor' => $request->id_sensor ?? $data->id_sensor,
            'mesure_datetime' => $request->mesure_datetime ?? $data->mesure_datetime,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sensor data updated successfully'
        ], 200);
    }

    /**
     * DELETE /api/sensor-data/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if (strtolower($user->role) !== 'superadmin') {
            $exists = DB::table('sensor_data')
                ->join('sensors', 'sensor_data.id_sensor', '=', 'sensors.id')
                ->join('equipement', 'sensors.id_equipement', '=', 'equipement.id')
                ->join('devices', 'equipement.id_device', '=', 'devices.id')
                ->join('sites', 'devices.id_site', '=', 'sites.id')
                ->where('sensor_data.id', $id)
                ->where('sites.id_client', $user->client_id)
                ->exists();

            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized action'
                ], 403);
            }
        }

        DB::table('sensor_data')->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sensor data deleted successfully'
        ], 200);
    }

    /**
     * GET /api/sensor-data/stats
     * Statistiques: moyenne, min, max, count
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        $query = DB::table('sensor_data')
            ->join('sensors', 'sensor_data.id_sensor', '=', 'sensors.id')
            ->join('equipement', 'sensors.id_equipement', '=', 'equipement.id')
            ->join('devices', 'equipement.id_device', '=', 'devices.id')
            ->join('sites', 'devices.id_site', '=', 'sites.id');

        if (strtolower($user->role) !== 'superadmin') {
            $query->where('sites.id_client', $user->client_id);
        }

        if ($request->has('id_sensor')) {
            $query->where('sensor_data.id_sensor', $request->id_sensor);
        }

        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('sensor_data.reception_datetime', [$request->from, $request->to]);
        }

        $stats = $query->select(
            DB::raw('COUNT(*) as total_records'),
            DB::raw('AVG(value) as average'),
            DB::raw('MIN(value) as minimum'),
            DB::raw('MAX(value) as maximum'),
            DB::raw('SUM(value) as total')
        )->first();

        return response()->json([
            'success' => true,
            'data' => $stats
        ], 200);
    }
}