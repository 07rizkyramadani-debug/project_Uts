<?php

use Illuminate\Support\Facades\Route;
use Modules\Circulation\Controllers\LoanController;
use Modules\Circulation\Controllers\MyLoanController;

Route::middleware(['web', 'auth:sanctum'])->group(function () {
    // Khusus admin: daftar terhapus, restore, dan soft delete peminjaman
    Route::middleware('role:admin')->group(function () {
        Route::get('loans/trashed', [LoanController::class, 'trashed'])->name('loans.trashed');
        Route::post('loans/{id}/restore', [LoanController::class, 'restore'])->whereNumber('id')->name('loans.restore');
        Route::delete('loans/{loan}', [LoanController::class, 'destroy'])->name('loans.destroy');
    });

    // User biasa: lihat pinjaman sendiri dan ajukan peminjaman (pustakawan yang menyetujui)
    Route::middleware('role:user')->group(function () {
        Route::get('my-loans', [MyLoanController::class, 'index'])->name('my-loans.index');
        Route::get('my-loans/create', [MyLoanController::class, 'create'])->name('my-loans.create');
        Route::post('my-loans', [MyLoanController::class, 'store'])->name('my-loans.store');
    });

    // Admin & pustakawan: operasional sirkulasi (user biasa tidak boleh)
    Route::middleware('role:admin,pustakawan')->group(function () {
        Route::get('loans', [LoanController::class, 'index'])->name('loans.index');
        Route::get('loans/create', [LoanController::class, 'create'])->name('loans.create');
        Route::post('loans', [LoanController::class, 'store'])->name('loans.store');
        Route::get('loans/{loan}', [LoanController::class, 'show'])->whereNumber('loan')->name('loans.show');
        Route::post('loans/{loan}/approve', [LoanController::class, 'approve'])->whereNumber('loan')->name('loans.approve');
        Route::post('loans/{loan}/reject', [LoanController::class, 'reject'])->whereNumber('loan')->name('loans.reject');
        Route::post('loans/{loan}/return', [LoanController::class, 'returnLoan'])->whereNumber('loan')->name('loans.return');
    });
});
