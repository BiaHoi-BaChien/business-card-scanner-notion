<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExtractionController;
use App\Http\Controllers\NotionController;
use App\Http\Controllers\PasskeyController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/auth/status', [AuthController::class, 'status']);
Route::post('/passkey/register', [PasskeyController::class, 'register'])->middleware('auth.session');
Route::post('/passkey/login', [PasskeyController::class, 'login']);

Route::middleware('auth.session')->group(function () {
    Route::post('/extract', [ExtractionController::class, 'extract']);
    Route::post('/notion/verify', [NotionController::class, 'verify']);
    Route::post('/notion/create', [NotionController::class, 'create']);
});
