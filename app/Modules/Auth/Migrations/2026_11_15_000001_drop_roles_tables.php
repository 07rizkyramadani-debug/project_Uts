<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Otorisasi memakai kolom users.role (admin | pustakawan | user), sehingga tabel roles dan
 * role_user yang tidak pernah dipakai untuk pengecekan hak akses dihapus agar tidak ada data ganda.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }

    public function down(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });
    }
};
