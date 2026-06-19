<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return response()->json([
        'message' => 'DICT Region II Official Business Pass Slip System API',
        'version' => '1.0.0',
        'status' => 'online',
    ]);
});

Route::get('/verify/{qr_code}', [\App\Http\Controllers\Web\VerificationController::class, 'verify'])
    ->name('verify.qr');