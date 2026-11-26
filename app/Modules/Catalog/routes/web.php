<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Controllers\AuthorController;
use Modules\Catalog\Controllers\BookController;
use Modules\Catalog\Controllers\BookCoverController;
use Modules\Catalog\Controllers\BookStockReportController;
use Modules\Catalog\Controllers\CategoryController;
use Modules\Catalog\Controllers\PublisherController;

// Gambar sampul buku (publik, tanpa sesi): URL relatif sehingga tidak bergantung pada APP_URL / storage:link
Route::get('book-covers/{file}', [BookCoverController::class, 'show'])
    ->where('file', '[A-Za-z0-9][A-Za-z0-9._-]*\.(jpg|jpeg|png|webp)')
    ->name('books.cover');

Route::middleware(['web', 'auth:sanctum'])->group(function () {
    // Khusus admin: kelola buku, daftar terhapus, dan restore (didefinisikan sebelum books/{book})
    Route::middleware('role:admin')->group(function () {
        Route::get('books/trashed', [BookController::class, 'trashed'])->name('books.trashed');
        Route::post('books/{id}/restore', [BookController::class, 'restore'])->whereNumber('id')->name('books.restore');
        Route::resource('books', BookController::class)->except(['index', 'show']);

        // Data master katalog: penulis, kategori, penerbit (daftar penulis/kategori tetap bisa dilihat semua user)
        Route::resource('authors', AuthorController::class)->except(['index', 'show']);
        Route::resource('categories', CategoryController::class)->except(['index', 'show']);
        Route::resource('publishers', PublisherController::class)->except(['show']);

        // Laporan stok habis dari pustakawan
        Route::get('stock-reports', [BookStockReportController::class, 'index'])->name('stock-reports.index');
        Route::patch('stock-reports/{report}/resolve', [BookStockReportController::class, 'resolve'])
            ->whereNumber('report')->name('stock-reports.resolve');
    });

    // Pustakawan: hanya memantau stok dan melaporkan buku yang habis ke admin (tidak bisa meminjam)
    Route::post('books/{book}/report-stock', [BookStockReportController::class, 'store'])
        ->middleware('role:pustakawan')->whereNumber('book')->name('books.report-stock');

    Route::resource('books', BookController::class)->only(['index', 'show']);
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('authors', [AuthorController::class, 'index'])->name('authors.index');
});
