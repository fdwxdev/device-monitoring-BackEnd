<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\SensorController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // AUTH ROUTES
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // SUPER ADMIN ROUTES
    Route::middleware('role:superAdmin')->group(function () {
        Route::apiResource('clients', ClientController::class);
        Route::apiResource('sites', SiteController::class);
        Route::apiResource('devices', DeviceController::class);
       // Route::apiResource('equipments', EquipmentController::class);
        Route::apiResource('sensors', SensorController::class);
    });

    // CLIENT ADMIN ROUTES
    Route::middleware('role:adminClient')->group(function () {
        // Consultation
        Route::get('/client/sites', [SiteController::class, 'index']); // استعملت index حيت هي اللي صاوبنا فيها الـ logic
       // Route::get('/client/equipments', [EquipmentController::class, 'clientEquipments']);
        Route::get('/client/sensors', [SensorController::class, 'clientSensors']);

        // Modification Settings
       // Route::put('/equipments/{id}/settings', [EquipmentController::class, 'updateSettings']);
        Route::put('/sensors/{id}/thresholds', [SensorController::class, 'updateThresholds']);
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