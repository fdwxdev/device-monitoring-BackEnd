<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SiteController extends Controller
{
    /**
     * GET /api/sites
     * هاد الـ function كترجع ليك الـ equipments ديال الكليان اللي مكونيكطي
     */
    public function index()
    {
        $clientId = auth()->user::client_id;

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
     * POST /api/sites
     * إضافة Site جديد
     */
    public function store(Request $request)
    {
        // التحقق من البيانات (Validation)
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string',
            'adress'    => 'required|string',
            'city'      => 'required|string',
            'id_client' => 'required|integer', // استعملت id_client كيفما عندك فالتصويرة
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // إدخال البيانات للداتابيز
        $id = DB::table('sites')->insertGetId([
            'name'      => $request->name,
            'adress'    => $request->adress,
            'city'      => $request->city,
            'id_client' => $request->id_client,
        ]);

        // جلب السطر اللي تزاد باش نرجعوه فـ JSON
        $site = DB::table('sites')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Site created successfully',
            'data'    => $site
        ], 201);
    }

    /**
     * GET /api/sites/{id}
     * عرض معلومات Site واحد
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
     * PUT /api/sites/{id}
     * تعديل Site
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
     * حذف Site
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