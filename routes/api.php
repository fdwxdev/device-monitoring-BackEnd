<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\ClientDashboardController;
//use App\Http\Controllers\SuperAdminDashboardController;
     use App\Http\Controllers\SuperAdminDashboardController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('token.auth')->group(function () {

    // AUTH ROUTES
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // SUPER ADMIN ROUTES
    Route::middleware('role:superAdmin')->group(function () {
    Route::apiResource('clients', ClientController::class);
     Route::apiResource('sites', SiteController::class);
    // Route::apiResource('equipments', EquipmentController::class);
     Route::apiResource('sensors', SensorController::class);





    });
        Route::apiResource('devices', DeviceController::class);
       // Route::get('/admin/dashboard-stats', [SuperAdminDashboardController::class, 'getStats']);


    // CLIENT ADMIN ROUTES
    Route::middleware('role:adminClient,client')->group(function () {
        // Consultation
        Route::get('/client/sites', [SiteController::class, 'index']); 
       // Route::get('/client/equipments', [EquipmentController::class, 'clientEquipments']);
        Route::get('/client/sensors', [SensorController::class, 'clientSensors']);

        // Modification Settings
       // Route::put('/equipments/{id}/settings', [EquipmentController::class, 'updateSettings']);
        Route::put('/sensors/{id}/thresholds', [SensorController::class, 'updateThresholds']);

        // Nouvelles routes sécurisées pour le Dashboard Client
        Route::get('/client/dashboard/stats', [ClientDashboardController::class, 'getStats']);
        Route::get('/client/devices', [ClientDashboardController::class, 'getDevices']);
        Route::get('/client/devices/{id}', [ClientDashboardController::class, 'getDeviceDetails']);
        Route::get('/client/devices/{id}/history', [ClientDashboardController::class, 'getDeviceHistory']);
        Route::get('/client/alarms', [ClientDashboardController::class, 'getAlarms']);
        Route::put('/client/profile', [ClientDashboardController::class, 'updateProfile']);
        Route::put('/client/profile/password', [ClientDashboardController::class, 'updatePassword']);
    });

    // VIEWER ROUTES
    Route::middleware('role:viewer')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json([
                'success' => true,
                'message' => 'Viewer Access'
            ]);
        });
    });

});
