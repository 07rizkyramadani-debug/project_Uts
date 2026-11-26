<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perpustakaan terbuka untuk umum tanpa masa berlaku: selama akunnya ada, anggota bisa meminjam.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('memberships');
    }

    public function down(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('type')->default('regular');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }
};
