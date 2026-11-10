<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarrierApiController;
use App\Http\Controllers\Api\ExceptionApiController;
use App\Http\Controllers\Api\LocationApiController;
use App\Http\Controllers\Api\ShipmentApiController;
use App\Http\Controllers\Api\TrackingApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CargoTrace REST API Routes
|--------------------------------------------------------------------------
| Sanctum-protected RESTful API endpoints adhering to UAS specifications.
*/

// Public Authentication endpoints
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Authenticated API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Shipments CRUD & Operations
    Route::get('/shipments', [ShipmentApiController::class, 'index']);
    Route::post('/shipments', [ShipmentApiController::class, 'store']);
    Route::get('/shipments/{id}', [ShipmentApiController::class, 'show']);
    Route::match(['put', 'patch'], '/shipments/{id}', [ShipmentApiController::class, 'update']);
    Route::delete('/shipments/{id}', [ShipmentApiController::class, 'destroy']);

    // Tracking Events
    Route::get('/shipments/{id}/tracking', [TrackingApiController::class, 'index']);
    Route::post('/shipments/{id}/tracking', [TrackingApiController::class, 'store']);

    // Shipment Exceptions
    Route::get('/shipments/{id}/exceptions', [ExceptionApiController::class, 'index']);
    Route::post('/shipments/{id}/exceptions', [ExceptionApiController::class, 'store']);
    Route::patch('/exceptions/{id}', [ExceptionApiController::class, 'update']);

    // Carriers & Locations
    Route::get('/carriers', [CarrierApiController::class, 'index']);
    Route::post('/carriers', [CarrierApiController::class, 'store']);

    Route::get('/locations', [LocationApiController::class, 'index']);
    Route::post('/locations', [LocationApiController::class, 'store']);
});

