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
| REST API Routes (CargoTrace)
| Slide 12 Requirements: Authentication, Shipments, Tracking, Exceptions,
| Carriers, Locations.
|--------------------------------------------------------------------------
*/

// Public Authentication Endpoints
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Authenticated Endpoints via Laravel Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Shipments
    Route::get('/shipments', [ShipmentApiController::class, 'index']);
    Route::post('/shipments', [ShipmentApiController::class, 'store']);
    Route::get('/shipments/{id}', [ShipmentApiController::class, 'show']);
    Route::match(['put', 'patch'], '/shipments/{id}', [ShipmentApiController::class, 'update']);
    Route::delete('/shipments/{id}', [ShipmentApiController::class, 'destroy']);

    // Tracking Events
    Route::get('/shipments/{id}/tracking', [TrackingApiController::class, 'index']);
    Route::post('/shipments/{id}/tracking', [TrackingApiController::class, 'store']);

    // Carriers & Locations
    Route::get('/carriers', [CarrierApiController::class, 'index']);
    Route::post('/carriers', [CarrierApiController::class, 'store']);
    Route::get('/locations', [LocationApiController::class, 'index']);
    Route::post('/locations', [LocationApiController::class, 'store']);

    // Exceptions
    Route::get('/shipments/{id}/exceptions', [ExceptionApiController::class, 'index']);
    Route::post('/shipments/{id}/exceptions', [ExceptionApiController::class, 'store']);
    Route::patch('/exceptions/{id}', [ExceptionApiController::class, 'update']);
});
