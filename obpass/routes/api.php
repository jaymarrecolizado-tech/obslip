<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CertificateController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\GuardController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PassSlipController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/v1/login', [AuthController::class, 'login']);
Route::post('/v1/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/v1/reset-password', [AuthController::class, 'resetPassword']);



// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/v1/logout', [AuthController::class, 'logout']);
    Route::get('/v1/me', [AuthController::class, 'me']);

    // Pass Slips
    Route::apiResource('v1/pass-slips', PassSlipController::class);
    Route::post('/v1/pass-slips/{pass_slip}/submit', [PassSlipController::class, 'submit']);
    Route::post('/v1/pass-slips/{pass_slip}/approve', [PassSlipController::class, 'approve']);
    Route::post('/v1/pass-slips/{pass_slip}/return', [PassSlipController::class, 'returnSlip']);
    Route::post('/v1/pass-slips/{pass_slip}/cancel', [PassSlipController::class, 'cancel']);
    Route::get('/v1/pass-slips/{pass_slip}/pdf', [PassSlipController::class, 'pdf']);

    // Employees
    Route::apiResource('v1/employees', EmployeeController::class);

    // Vehicles
    Route::apiResource('v1/vehicles', VehicleController::class);

    // Departments
    Route::apiResource('v1/departments', DepartmentController::class);

    // Certificates
    Route::apiResource('v1/certificates', CertificateController::class);
    Route::post('/v1/certificates/{certificate}/verify', [CertificateController::class, 'verify']);
    Route::get('/v1/certificates/{certificate}/pdf', [CertificateController::class, 'pdf']);

    // Notifications
    Route::get('/v1/notifications', [NotificationController::class, 'index']);
    Route::put('/v1/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::put('/v1/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/v1/device-tokens', [NotificationController::class, 'storeDeviceToken']);

    // Guard Actions
    Route::get('/v1/guard/search-slip', [GuardController::class, 'searchSlip']);
    Route::post('/v1/guard/scan-qr', [GuardController::class, 'scanQr']);
    Route::post('/v1/guard/log-departure/{pass_slip}', [GuardController::class, 'logDeparture']);
    Route::post('/v1/guard/log-arrival/{pass_slip}', [GuardController::class, 'logArrival']);
});
