<?php

use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\SensorDataController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\ClientDashboardController;
use App\Http\Controllers\SuperAdminDashboardController;
use App\Http\Controllers\UserController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('token.auth')->group(function () {

    // AUTH ROUTES
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // SHARED ROUTES (SUPER ADMIN & ADMIN CLIENT)
    Route::middleware('role:superAdmin,adminClient')->group(function () {
        Route::apiResource('utilisateurs', UserController::class);
    });

    // SUPER ADMIN ROUTES
    Route::middleware('role:superAdmin')->group(function () {
        Route::get('/admin/dashboard-stats', [SuperAdminDashboardController::class, 'getStats']);

        Route::apiResource('sites', SiteController::class);
        Route::apiResource('sensors', SensorController::class);
        Route::apiResource('equipments', EquipmentController::class);
        Route::apiResource('devices', DeviceController::class);
        Route::apiResource('clients', ClientController::class);
        Route::get('/clients/{id}/sites', [ClientController::class, 'sites']);

        Route::apiResource('sensor-data', SensorDataController::class);
        Route::get('/sensor-data/stats', [SensorDataController::class, 'stats']);
        Route::get('/alerts', [SensorDataController::class, 'getAlerts']);
        Route::get('/sensors/{id}/data', [SensorDataController::class, 'getBySensor']);
        Route::get('/notifications', [ClientDashboardController::class, 'getNotifications']);
    });


    
    Route::middleware('role:adminClient,client,user')->group(function () {
        Route::get('/client/sites', [SiteController::class, 'index']);
        Route::get('/client/sites/{id}', [SiteController::class, 'show']);
        Route::get('/client/sensors', [SensorController::class, 'clientSensors']);
        Route::put('/sensors/{id}/thresholds', [SensorController::class, 'updateThresholds']);

        Route::get('/client/dashboard/stats', [ClientDashboardController::class, 'getStats']);
        Route::get('/client/devices', [ClientDashboardController::class, 'getDevices']);
        Route::get('/client/devices/{id}', [ClientDashboardController::class, 'getDeviceDetails']);
        Route::get('/client/devices/{id}/history', [ClientDashboardController::class, 'getDeviceHistory']);
        Route::get('/client/alarms', [ClientDashboardController::class, 'getAlarms']);
        Route::put('/client/profile', [ClientDashboardController::class, 'updateProfile']);
        Route::put('/client/profile/password', [ClientDashboardController::class, 'updatePassword']);
        Route::get('/client/alerts', [SensorDataController::class, 'getAlerts']);
        Route::get('/client/notifications', [ClientDashboardController::class, 'getNotifications']);
    });

    // VIEWER ROUTES
    Route::middleware('role:viewer')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json(['success' => true, 'message' => 'Viewer Access']);
        });
    });
});