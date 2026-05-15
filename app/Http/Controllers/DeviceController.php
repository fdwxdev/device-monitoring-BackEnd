<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    /**
     * 1. عرض قائمة الأجهزة الخاصة بالكليان اللي مكونيكطي
     */
    public function index()
    {
        $clientId = auth()->user::client_id;

        $devices = DB::table('devices')
            ->join('sites', 'devices.id_site', '=', 'sites.id')
            ->where('sites.id_client', $clientId)
            ->select('devices.*', 'sites.name as site_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $devices
        ], 200);
    }

    /**
     * 2. إضافة جهاز جديد
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'id_site' => 'required|integer|exists:sites,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // تأكد أن الـ site اللي اختار الكليان تابع ليه نيت ماشي ديال شي حد آخر
        $siteOwnedByClient = DB::table('sites')
            ->where('id', $request->id_site)
            ->where('id_client', auth()->user::client_id)
            ->exists();

        if (!$siteOwnedByClient) {
            return response()->json(['message' => 'Unauthorized site'], 403);
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

    /**
     * 3. عرض معلومات جهاز واحد محدد
     */
    public function show($id)
    {
        $clientId = auth()->user::client_id;

        $device = DB::table('devices')
            ->join('sites', 'devices.id_site', '=', 'sites.id')
            ->where('sites.id_client', $clientId)
            ->where('devices.id', $id)
            ->select('devices.*', 'sites.name as site_name')
            ->first();

        if (!$device) {
            return response()->json(['message' => 'Device not found or unauthorized'], 404);
        }

        return response()->json($device, 200);
    }

    /**
     * 4. تعديل جهاز
     */
    public function update(Request $request, $id)
    {
        $clientId = auth()->user::client_id;

        // التحقق من الملكية قبل التعديل
        $device = DB::table('devices')
            ->join('sites', 'devices.id_site', '=', 'sites.id')
            ->where('sites.id_client', $clientId)
            ->where('devices.id', $id)
            ->first();

        if (!$device) {
            return response()->json(['message' => 'Device not found or unauthorized'], 404);
        }

        DB::table('devices')->where('id', $id)->update([
            'name' => $request->name ?? $device->name,
            'id_site' => $request->id_site ?? $device->id_site,
        ]);

        return response()->json(['message' => 'Device updated successfully'], 200);
    }

    /**
     * 5. حذف جهاز
     */
    public function destroy($id)
    {
        $clientId = auth()->user::client_id;

        // التحقق من الملكية قبل الحذف
        $exists = DB::table('devices')
            ->join('sites', 'devices.id_site', '=', 'sites.id')
            ->where('sites.id_client', $clientId)
            ->where('devices.id', $id)
            ->exists();

        if (!$exists) {
            return response()->json(['message' => 'Unauthorized action'], 403);
        }

        DB::table('devices')->where('id', $id)->delete();

        return response()->json(['message' => 'Device deleted successfully'], 200);
    }

    /**
     * 6. الـ Function اللي طلبتي قبل: جلب الـ equipments ديال كليان معين
     * تقدري تزيديها هنا أو فـ EquipmentController
     */
    public function getEquipments()
    {
        $clientId = auth()->user::client_id;

        $equipments = DB::table('equipments')
            ->join('devices', 'equipments.id_device', '=', 'devices.id')
            ->join('sites', 'devices.id_site', '=', 'sites.id')
            ->where('sites.id_client', $clientId)
            ->select('equipments.*', 'devices.name as device_name')
            ->get();

        return response()->json($equipments, 200);
    }
}
