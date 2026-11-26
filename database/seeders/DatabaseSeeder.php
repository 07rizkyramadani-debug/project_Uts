<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun bawaan: admin, pustakawan, dan user biasa (peran disimpan di kolom users.role)
        $this->call(AdminUserSeeder::class);

        // Sistem Perpustakaan (Catalog, Membership, Circulation)
        $this->call(LibrarySeeder::class);
    }
}
