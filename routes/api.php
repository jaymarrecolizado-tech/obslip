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

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Auth routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

    // Pass slip routes
    Route::apiResource('pass-slips', PassSlipController::class);
    Route::post('/pass-slips/{pass_slip}/submit', [PassSlipController::class, 'submit']);
    Route::post('/pass-slips/{pass_slip}/approve', [PassSlipController::class, 'approve']);
    Route::post('/pass-slips/{pass_slip}/return', [PassSlipController::class, 'return']);
    Route::post('/pass-slips/{pass_slip}/cancel', [PassSlipController::class, 'cancel']);
    Route::get('/pass-slips/{pass_slip}/pdf', [PassSlipController::class, 'pdf']);
    Route::get('/guard/search-slip', [PassSlipController::class, 'search']);
    Route::post('/guard/scan-qr', [PassSlipController::class, 'scanQr']);
    Route::post('/guard/log-departure/{pass_slip}', [PassSlipController::class, 'logDeparture']);
    Route::post('/guard/log-arrival/{pass_slip}', [PassSlipController::class, 'logArrival']);

    // Employee routes
    Route::apiResource('employees', EmployeeController::class);
    Route::get('/employees/active', [EmployeeController::class, 'active']);

    // Vehicle routes
    Route::apiResource('vehicles', VehicleController::class);
    Route::get('/vehicles/available', [VehicleController::class, 'available']);

    // Department routes
    Route::apiResource('departments', DepartmentController::class);
    Route::get('/departments/active', [DepartmentController::class, 'active']);

    // Certificate routes
    Route::apiResource('certificates', CertificateController::class);
    Route::post('/certificates/{certificate}/verify', [CertificateController::class, 'verify']);

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index'])->middleware('auth:sanctum');
    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->middleware('auth:sanctum');
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->middleware('auth:sanctum');
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->middleware('auth:sanctum');
    Route::post('/device-tokens', [NotificationController::class, 'storeDeviceToken']);
    Route::delete('/device-tokens/{token}', [NotificationController::class, 'deleteDeviceToken'])->middleware('auth:sanctum');

    // Guard routes
    Route::get('/guard/today-activity', [GuardController::class, 'todayActivity']);
});

// Public QR verification (no auth required)
Route::get('/verify/{qr_code}', [\App\Http\Controllers\Web\VerificationController::class, 'verify'])
    ->name('verify.qr');