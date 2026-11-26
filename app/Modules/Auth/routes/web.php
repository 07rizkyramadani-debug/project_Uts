<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\AdminDashboardController;

// Route untuk admin (grup 'web' wajib agar sesi/CSRF aktif; lalu login Sanctum + role admin)
Route::middleware(['web', 'auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
        ->name('admin.dashboard');
});

// Route untuk pengguna yang sudah login
Route::middleware(['web', 'auth:sanctum'])->group(function () {
    Route::get('/user/dashboard', [DashboardController::class, 'index'])
        ->name('user.dashboard');
});
