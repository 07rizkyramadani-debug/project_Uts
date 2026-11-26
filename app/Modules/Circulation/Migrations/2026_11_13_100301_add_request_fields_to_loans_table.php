<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengajuan peminjaman dari user (status pending/rejected) belum punya tanggal pinjam
     * maupun jatuh tempo; keduanya baru terisi saat pustakawan menyetujui.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dateTime('borrowed_at')->nullable()->default(null)->change();
            $table->dateTime('due_at')->nullable()->change();
            $table->foreignId('decided_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at')->nullable()->after('decided_by');
            $table->string('rejection_reason')->nullable()->after('decided_at');
        });
    }

    /** Pengajuan pending/ditolak harus dihapus lebih dulu sebelum rollback (kolom tanggal kembali wajib diisi). */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('decided_by');
            $table->dropColumn(['decided_at', 'rejection_reason']);
            $table->dateTime('due_at')->nullable(false)->change();
            $table->dateTime('borrowed_at')->useCurrent()->nullable(false)->change();
        });
    }
};
