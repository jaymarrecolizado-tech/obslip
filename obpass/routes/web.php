<?php

use App\Http\Controllers\Api\V1\PassSlipController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// QR verify (public, no auth required)
Route::get('/verify/{qr_code}', [PassSlipController::class, 'verifyQr']);
