<?php

use Illuminate\Support\Facades\Route;
use Modules\Circulation\Controllers\Api\LoanApiController;

Route::prefix('api')->middleware(['api', 'auth:sanctum', 'throttle:api', 'role:admin,pustakawan'])->group(function () {
    Route::post('loans', [LoanApiController::class, 'store'])->name('api.loans.store');
    Route::post('loans/{loan}/return', [LoanApiController::class, 'returnLoan'])->whereNumber('loan')->name('api.loans.return');
});
