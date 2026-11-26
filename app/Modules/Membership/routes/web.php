<?php

use Illuminate\Support\Facades\Route;
use Modules\Membership\Controllers\MemberController;
use Modules\Membership\Controllers\MembershipTypeController;

Route::middleware(['web', 'auth:sanctum'])->group(function () {
    // Admin & pustakawan: lihat daftar anggota dan mendaftarkan anggota baru
    Route::middleware('role:admin,pustakawan')->group(function () {
        Route::get('members', [MemberController::class, 'index'])->name('members.index');
        Route::resource('members', MemberController::class)->only(['create', 'store']);
    });

    // Khusus admin: ubah, hapus, daftar terhapus, dan restore
    Route::middleware('role:admin')->group(function () {
        Route::get('members/trashed', [MemberController::class, 'trashed'])->name('members.trashed');
        Route::post('members/{id}/restore', [MemberController::class, 'restore'])->whereNumber('id')->name('members.restore');
        Route::resource('members', MemberController::class)->only(['edit', 'update', 'destroy']);

        // Jenis keanggotaan (Umum/Mahasiswa/Dosen) beserta batas pinjamnya
        Route::resource('membership-types', MembershipTypeController::class)->except(['show']);
    });
});

