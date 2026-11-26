<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jenis Keanggotaan (Umum / Mahasiswa / Dosen): setiap jenis punya batas pinjam dan lama pinjam sendiri.
 * Relasi One-to-Many: satu jenis keanggotaan dimiliki banyak anggota.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->unsignedInteger('max_loans')->default(5);
            $table->unsignedInteger('loan_days')->default(14);
            $table->timestamps();
        });

        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('membership_type_id')->nullable()->after('user_id')
                ->constrained('membership_types')->nullOnDelete();
        });

        // Jenis bawaan; "Umum" memakai batas lama (5 buku, 14 hari) sehingga anggota yang sudah ada tidak berubah
        $now = now();
        DB::table('membership_types')->insert([
            ['name' => 'Umum', 'slug' => 'umum', 'max_loans' => 5, 'loan_days' => 14, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mahasiswa', 'slug' => 'mahasiswa', 'max_loans' => 7, 'loan_days' => 21, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dosen', 'slug' => 'dosen', 'max_loans' => 10, 'loan_days' => 30, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('members')->whereNull('membership_type_id')->update([
            'membership_type_id' => DB::table('membership_types')->where('slug', 'umum')->value('id'),
        ]);
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('membership_type_id');
        });

        Schema::dropIfExists('membership_types');
    }
};
