<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Controllers\Api\BookApiController;

Route::prefix('api')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('books', [BookApiController::class, 'index'])->name('api.books.index');
});
