<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\Api\AuthTokenController;

Route::prefix('api')->middleware('api')->group(function () {
    // Publik, dibatasi rate limiter ketat agar tidak bisa di-brute-force
    Route::middleware('throttle:api-auth')->group(function () {
        Route::post('register', [AuthTokenController::class, 'register'])->name('api.register');
        Route::post('login', [AuthTokenController::class, 'login'])->name('api.login');
    });

    // Terproteksi Sanctum (token)
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::get('me', [AuthTokenController::class, 'me'])->name('api.me');
        Route::post('logout', [AuthTokenController::class, 'logout'])->name('api.logout');
    });
});
