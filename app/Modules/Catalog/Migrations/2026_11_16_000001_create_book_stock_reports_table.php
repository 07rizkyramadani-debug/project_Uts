<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laporan stok habis: pustakawan memantau stok buku dan melaporkan buku yang habis ke admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_stock_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->string('status')->default('open'); // open | resolved
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['book_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_stock_reports');
    }
};
