<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarrierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExceptionController;
use App\Http\Controllers\ExternalApiController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\TrackingEventController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (CargoTrace Platform)
|--------------------------------------------------------------------------
*/

// Landing redirect
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Quick Demo Login (For portfolio / examiner live testing)
Route::get('/demo-login/{role}', [AuthController::class, 'quickLogin'])->name('demo.login');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Dashboard & Analytics
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Shipments
    Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
    Route::get('/shipments/{id}', [ShipmentController::class, 'show'])->name('shipments.show');

    // Operations (Admin & Operator only)
    Route::middleware('role:admin,operator')->group(function () {
        Route::get('/shipments-create', [ShipmentController::class, 'create'])->name('shipments.create');
        Route::post('/shipments', [ShipmentController::class, 'store'])->name('shipments.store');
        Route::get('/shipments/{id}/edit', [ShipmentController::class, 'edit'])->name('shipments.edit');
        Route::put('/shipments/{id}', [ShipmentController::class, 'update'])->name('shipments.update');
        Route::post('/shipments/{id}/transition', [ShipmentController::class, 'transition'])->name('shipments.transition');
        
        // Tracking events
        Route::post('/shipments/{id}/tracking', [TrackingEventController::class, 'store'])->name('tracking.store');
        
        // Exceptions
        Route::post('/shipments/{id}/exceptions', [ExceptionController::class, 'store'])->name('exceptions.store');
        Route::patch('/exceptions/{id}', [ExceptionController::class, 'update'])->name('exceptions.update');
    });

    // Exceptions list view
    Route::get('/exceptions', [ExceptionController::class, 'index'])->name('exceptions.index');

    // In-App Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');

    // External Services Live Integration Inspector
    Route::get('/integrations', [ExternalApiController::class, 'index'])->name('integrations.index');

    // Administrator Only Routes
    Route::middleware('role:admin')->group(function () {
        // Shipment Deletion
        Route::delete('/shipments/{id}', [ShipmentController::class, 'destroy'])->name('shipments.destroy');

        // Carriers Management
        Route::get('/carriers', [CarrierController::class, 'index'])->name('carriers.index');
        Route::post('/carriers', [CarrierController::class, 'store'])->name('carriers.store');
        Route::post('/carriers/{id}/toggle', [CarrierController::class, 'toggle'])->name('carriers.toggle');

        // Locations Management
        Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
        Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');

        // Audit Logs Explorer
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
    });
});
