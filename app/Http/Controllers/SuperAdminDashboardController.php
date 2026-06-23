<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 

class SuperAdminDashboardController extends Controller
{
    public function getStats()
    {
        try {
    // Query Builder
            $clientsCount = DB::table('clients')->count();
            $sitesCount = DB::table('sites')->count();
            
            $onlineCount = DB::table('devices')->where('status', 'Online')->count();
            $offlineCount = DB::table('devices')->where('status', 'Offline')->count();
            
            $totalDevices = $onlineCount + $offlineCount;
            $connectionRate = $totalDevices > 0 ? round(($onlineCount / $totalDevices) * 100) . '%' : '0%';

            $activeAlertsCount = DB::table('sensors')
                ->whereNotNull('value')
                ->where(function ($query) {
                    $query->whereColumn('value', '>', 'max_threshold')
                          ->orWhereColumn('value', '<', 'min_threshold');
                })->count();

            $thermalHistory = [
                ['name' => '00:00', 'temp' => 22],
                ['name' => '04:00', 'temp' => 21],
                ['name' => '08:00', 'temp' => 25],
                ['name' => '12:00', 'temp' => 28],
                ['name' => '16:00', 'temp' => 27],
                ['name' => '20:00', 'temp' => 23],
            ];

            return response()->json([
                'success' => true,
                'clients_count' => $clientsCount,
                'sites_count' => $sitesCount,
                'online_count' => $onlineCount,
                'offline_count' => $offlineCount,
                'active_alerts_count' => $activeAlertsCount, 
                'connection_rate' => $connectionRate,
                'thermal_history' => $thermalHistory
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Erreur base de données: ' . $e->getMessage()
            ], 500);
        }
    }
}