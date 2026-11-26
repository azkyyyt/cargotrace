<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarrierController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExceptionController;
use App\Http\Controllers\ExternalApiController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\TrackingEventController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (CargoTrace Platform)
| Strictly aligned with RBAC specification (Slide 05 & 13)
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
    // Dashboard & Analytics (All authenticated roles, scoped per user role)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Operational Shipments (Logistics Operator ONLY: Create, Update, Transition, Tracking, Exceptions)
    // Placed before /shipments/{id} wildcard if needed, using explicit /shipments-create path
    Route::middleware('role:operator')->group(function () {
        Route::get('/shipments-create', [ShipmentController::class, 'create'])->name('shipments.create');
        Route::post('/shipments', [ShipmentController::class, 'store'])->name('shipments.store');
        Route::get('/shipments/{id}/edit', [ShipmentController::class, 'edit'])->name('shipments.edit');
        Route::put('/shipments/{id}', [ShipmentController::class, 'update'])->name('shipments.update');
        Route::post('/shipments/{id}/transition', [ShipmentController::class, 'transition'])->name('shipments.transition');
        Route::post('/shipments/{id}/weather-check', [ShipmentController::class, 'checkWeatherImpact'])->name('shipments.weatherCheck');

        // Tracking events
        Route::post('/shipments/{id}/tracking', [TrackingEventController::class, 'store'])->name('tracking.store');

        // Exceptions
        Route::post('/shipments/{id}/exceptions', [ExceptionController::class, 'store'])->name('exceptions.store');
        Route::patch('/exceptions/{id}', [ExceptionController::class, 'update'])->name('exceptions.update');
    });

    // Shipments & Exceptions View (Admin views all, Operator views all, Customer strictly scoped to own)
    Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
    Route::get('/shipments/{id}', [ShipmentController::class, 'show'])->name('shipments.show');
    Route::get('/exceptions', [ExceptionController::class, 'index'])->name('exceptions.index');

    // In-App Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');

    // External Services Inspector & Location Autocomplete Search
    Route::get('/integrations', [ExternalApiController::class, 'index'])->name('integrations.index');
    Route::get('/locations/search', [LocationController::class, 'search'])->name('locations.search');

    // Administrator Only Routes (Slide 05: Manage Users, Config, Carriers, Locations, Audit Log)
    Route::middleware('role:admin')->group(function () {
        // Shipment Deletion
        Route::delete('/shipments/{id}', [ShipmentController::class, 'destroy'])->name('shipments.destroy');

        // User & Role Management
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [UserManagementController::class, 'destroy'])->name('users.destroy');

        // System Configuration & Delay Thresholds
        Route::get('/settings', [ConfigurationController::class, 'index'])->name('settings.index');
        Route::put('/settings', [ConfigurationController::class, 'update'])->name('settings.update');

        // Carriers Management
        Route::get('/carriers', [CarrierController::class, 'index'])->name('carriers.index');
        Route::post('/carriers', [CarrierController::class, 'store'])->name('carriers.store');
        Route::put('/carriers/{id}', [CarrierController::class, 'update'])->name('carriers.update');
        Route::post('/carriers/{id}/toggle', [CarrierController::class, 'toggle'])->name('carriers.toggle');
        Route::delete('/carriers/{id}', [CarrierController::class, 'destroy'])->name('carriers.destroy');

        // Locations Management
        Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
        Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
        Route::put('/locations/{id}', [LocationController::class, 'update'])->name('locations.update');
        Route::delete('/locations/{id}', [LocationController::class, 'destroy'])->name('locations.destroy');

        // Audit Logs Explorer
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
    });
});
