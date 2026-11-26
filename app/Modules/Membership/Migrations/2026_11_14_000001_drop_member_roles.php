<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Semua anggota kini memiliki batas pinjam yang sama (lihat Member::MAX_LOANS dan Member::LOAN_DAYS),
 * sehingga jenis anggota (Umum/Mahasiswa/Dosen) dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_role_id');
        });

        Schema::dropIfExists('member_roles');
    }

    public function down(): void
    {
        Schema::create('member_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('max_loans');
            $table->unsignedInteger('loan_days');
            $table->timestamps();
        });

        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('member_role_id')->nullable()->constrained('member_roles');
        });
    }
};
